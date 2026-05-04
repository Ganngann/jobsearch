<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserMatch;
use App\Services\MatchingService;
use Illuminate\Support\Facades\Log;

class AutoAiMatchingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forem:auto-ai';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Déclenche l\'analyse IA sur la meilleure offre disponible pour chaque utilisateur actif.';

    /**
     * Execute the console command.
     */
    public function handle(MatchingService $matchingService)
    {
        $this->info('Lancement du Chasseur de Pépites IA...');

        // 1. On ne cible que les utilisateurs actifs (connectés dans les 30 dernières minutes)
        $activeUsers = User::where('last_seen_at', '>=', now()->subMinutes(30))->get();

        if ($activeUsers->isEmpty()) {
            $this->comment('Aucun utilisateur actif trouvé. Fin du cycle.');
            return;
        }

        foreach ($activeUsers as $user) {
            $this->info("Analyse pour l'utilisateur : {$user->email}");

            // 2. On cherche le "Top 1" des offres non analysées avec un score Data > 75
            $topMatch = UserMatch::where('user_id', $user->id)
                ->whereNull('analyzed_at')
                ->where('pre_score', '>=', 75)
                ->orderBy('pre_score', 'desc')
                ->first();

            if (!$topMatch) {
                $this->line("  - Aucune offre haute performance trouvée pour le moment.");
                continue;
            }

            // 3. On déclenche le matching complet (qui va gérer le quota lui-même)
            $this->info("  - Pépite trouvée : Offre #{$topMatch->job_offer_id} (Score: {$topMatch->pre_score}%)");
            
            $matchingService->match($user, $topMatch->jobOffer, false, true);
            
            $this->info("  - Analyse IA lancée avec succès.");
        }

        $this->info('Cycle terminé.');
    }
}
