<?php

namespace App\Console\Commands;

use App\Models\JobOffer;
use App\Services\JobOfferService;
use Illuminate\Console\Command;

class ForemPullWorkerCommand extends Command
{
    protected $signature = 'forem:pull-worker {--sleep=5 : Secondes entre chaque requête} {--limit=100 : Nombre d\'itérations avant de s\'arrêter}';
    protected $description = 'Worker qui récupère les détails complets des offres au compte-goutte.';

    protected $jobOfferService;

    public function __construct(JobOfferService $jobOfferService)
    {
        parent::__construct();
        $this->jobOfferService = $jobOfferService;
    }

    public function handle()
    {
        $sleep = (int) $this->option('sleep');
        $limit = (int) $this->option('limit');
        $count = 0;

        $this->info("Démarrage du Pull Worker (Sleep: {$sleep}s)...");

        while ($count < $limit) {
            // 1. Priorité aux nouvelles offres (non détaillées)
            $jobOffer = JobOffer::where('status', 'active')
                ->where('is_detailed', false)
                ->orderBy('last_seen_at', 'DESC')
                ->first();

            // 2. Sinon, on rafraîchit les plus anciennes (maintenance)
            if (!$jobOffer) {
                $jobOffer = JobOffer::where('status', 'active')
                    ->where('is_detailed', true)
                    ->orderBy('detailed_at', 'ASC')
                    ->first();
            }

            if (!$jobOffer) {
                $this->comment("Aucune offre à traiter. Sommeil de 60s...");
                sleep(60);
                continue;
            }

            $this->line("[" . now()->format('H:i:s') . "] Traitement de #{$jobOffer->forem_id} : {$jobOffer->title}");
            
            try {
                $success = $this->jobOfferService->syncFullDetails($jobOffer);
                if ($success) {
                    $this->info("  --> Succès");
                } else {
                    $this->error("  --> Échec (API)");
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
