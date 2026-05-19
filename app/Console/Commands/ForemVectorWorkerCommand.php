<?php

namespace App\Console\Commands;

use App\Models\JobOffer;
use App\Models\Setting;
use App\Services\VectorService;
use Illuminate\Console\Command;

class ForemVectorWorkerCommand extends Command
{
    protected $signature = 'forem:vector-worker {--sleep=5 : Secondes entre chaque requête} {--limit=100 : Nombre d\'itérations avant de s\'arrêter}';
    protected $description = 'Worker qui vectorise (embedding) les offres d\'emploi au compte-goutte, si activé.';

    protected $vectorService;

    public function __construct(VectorService $vectorService)
    {
        parent::__construct();
        $this->vectorService = $vectorService;
    }

    public function handle()
    {
        $sleep = (int) $this->option('sleep');
        $limit = (int) $this->option('limit');
        $count = 0;

        $this->info("Démarrage du Vector Worker (Sleep: {$sleep}s)...");

        // On vérifie d'abord si la vectorisation continue est activée
        $isEnabled = Setting::get('enable_continuous_vectorization', '0') === '1';

        if (!$isEnabled) {
            $this->comment("La vectorisation continue est désactivée dans les paramètres. Arrêt.");
            return;
        }

        while ($count < $limit) {
            // Revérifier l'état à chaque itération pour pouvoir l'arrêter en direct depuis le dashboard
            if (Setting::get('enable_continuous_vectorization', '0') !== '1') {
                $this->comment("La vectorisation continue a été désactivée en cours d'exécution. Arrêt.");
                break;
            }

            // On cherche l'offre la plus récente (published_at), active, détaillée, mais sans vecteur
            $jobOffer = JobOffer::where('status', 'active')
                ->where('is_detailed', true)
                ->whereNull('vector_embedding')
                ->orderBy('published_at', 'DESC')
                ->first();

            if (!$jobOffer) {
                $this->comment("Aucune offre à vectoriser. Arrêt du worker (redémarrage prévu au prochain cycle).");
                break;
            }

            $this->line("[" . now()->format('H:i:s') . "] Vectorisation de #{$jobOffer->forem_id} : {$jobOffer->title}");

            try {
                Setting::set('heartbeat_vector-worker', now()->toDateTimeString());
                $success = $this->vectorService->updateJobVector($jobOffer);
                if ($success) {
                    $this->info("  --> Succès");
                } else {
                    $this->error("  --> Échec");
                }
            } catch (\Exception $e) {
                $this->error("  --> Erreur : " . $e->getMessage());
            }

            $count++;

            if ($count < $limit) {
                sleep($sleep);
            }
        }

        $this->info("Worker terminé après {$limit} itérations.");
    }
}
