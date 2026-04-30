<?php

namespace App\Console\Commands;

use App\Services\ForemApiService;
use App\Services\JobOfferService;
use Illuminate\Console\Command;

class ForemSyncCommand extends Command
{
    protected $signature = 'forem:sync {--pages=1 : Nombre de pages} {--rows=10 : Offres par page}';
    protected $description = 'Synchronise les offres depuis l\'API réelle du Forem (Version rapide, détails différés)';

    protected $foremApi;
    protected $jobOfferService;

    public function __construct(ForemApiService $foremApi, JobOfferService $jobOfferService)
    {
        parent::__construct();
        $this->foremApi = $foremApi;
        $this->jobOfferService = $jobOfferService;
    }

    public function handle()
    {
        $this->info('Début de la synchronisation (Importation des titres)...');

        $pages = (int) $this->option('pages');
        $rows = (int) $this->option('rows');

        for ($p = 1; $p <= $pages; $p++) {
            $this->comment("Traitement de la page {$p}/{$pages}...");
            $searchResults = $this->foremApi->searchJobs($p, $rows);
            
            $results = $searchResults['offreEmploiResumees'] ?? [];
            
            if (empty($results)) {
                $this->warn("Aucun résultat sur la page {$p}.");
                break;
            }

            foreach ($results as $item) {
                $jobId = $item['id'];
                $this->line("  Importation #{$jobId} : {$item['titre']}...");
                $this->jobOfferService->saveBasicOffer($item);
            }
            
            $this->info("    --> " . count($results) . " offres importées sur cette page.");
        }

        $this->info('Synchronisation terminée ! Les détails seront chargés à la visite.');
    }
}
