<?php

namespace App\Console\Commands;

use App\Models\Employer;
use App\Models\JobOffer;
use App\Models\UserMatch;
use App\Services\ForemApiService;
use App\Services\JobOfferService;
use App\Services\MatchingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetAndImportJobOffers extends Command
{
    protected $signature = 'app:reset-and-import {--count=1000}';
    protected $description = 'Réinitialise les offres et en importe 1000 nouvelles depuis le Forem';

    public function handle(JobOfferService $jobService, ForemApiService $foremApi, MatchingService $matchingService)
    {
        $this->info('--- NETTOYAGE DE LA BASE DE DONNÉES ---');
        
        // On désactive les clés étrangères pour le nettoyage
        DB::disableForeignKeyConstraints();
        
        DB::table('user_matches')->delete();
        DB::table('job_offer_skill')->delete();
        DB::table('job_offer_permit')->delete();
        DB::table('job_offer_language')->delete();
        DB::table('job_offer_sector')->delete();
        DB::table('job_offer_study')->delete();
        DB::table('job_offers')->delete();
        DB::table('employers')->delete();
        
        DB::enableForeignKeyConstraints();
        
        $this->info('Base de données nettoyée (Offres, Matches, Employeurs).');
        
        $targetCount = (int) $this->option('count');
        $this->info("Importation de {$targetCount} offres...");

        $importedCount = 0;
        $page = 1;
        $rowsPerPage = 50;

        $bar = $this->output->createProgressBar($targetCount);
        $bar->start();

        while ($importedCount < $targetCount) {
            $results = $foremApi->searchJobs([], $page, $rowsPerPage);
            $offres = $results['offres'] ?? [];

            if (empty($offres)) {
                $this->warn("\nPlus d'offres disponibles sur le Forem à la page {$page}.");
                break;
            }

            foreach ($offres as $item) {
                if ($importedCount >= $targetCount) break;

                // 1. Sauvegarde basique
                $jobOffer = $jobService->saveBasicOffer($item);

                // 2. Récupération des détails (Indispensable pour le métier et le matching)
                try {
                    $jobService->syncFullDetails($jobOffer);
                } catch (\Exception $e) {
                    // Silencieusement ignorer les erreurs de détail pour continuer l'import
                }

                $importedCount++;
                $bar->advance();
            }

            $page++;
            
            // Sécurité pour éviter de boucler à l'infini
            if ($page > 100) break; 
        }

        $bar->finish();
        $this->info("\n\n--- IMPORTATION TERMINÉE ---");
        $this->info("{$importedCount} offres importées avec succès.");
        
        $this->info("Lancement du matching global pour les utilisateurs...");
        foreach (\App\Models\User::all() as $user) {
            $matchingService->triggerMassMatch($user);
        }
        $this->info("Matching terminé.");
    }
}
