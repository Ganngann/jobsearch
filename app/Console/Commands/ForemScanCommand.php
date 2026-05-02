<?php

namespace App\Console\Commands;

use App\Models\JobOffer;
use App\Services\ForemApiService;
use App\Services\JobOfferService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ForemScanCommand extends Command
{
    protected $signature = 'forem:scan {--mode=flash : flash (page 1) ou cycle (page tournante)}';
    protected $description = 'Scanne l\'API du Forem pour découvrir de nouvelles offres ou rafraîchir le catalogue.';

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
        $mode = $this->option('mode');
        
        if ($mode === 'flash') {
            $this->runFlashScan();
        } else {
            $this->runCycleScan();
        }

        $this->cleanupOldOffers();
    }

    protected function runFlashScan()
    {
        $this->info("Exécution du Scan FLASH (Nouveautés)...");
        $results = $this->foremApi->searchJobs([], 1, 100);
        $this->importResults($results['offres'] ?? []);
    }

    protected function runCycleScan()
    {
        $currentPage = Cache::get('forem_scan_cycle_page', 1);
        $rowsPerPage = 1000;

        $this->info("Exécution du Scan CYCLE (Page {$currentPage}, rows={$rowsPerPage})...");
        
        $results = $this->foremApi->searchJobs([], $currentPage, $rowsPerPage);
        $offers = $results['offres'] ?? [];
        $total = $results['nombreTotalOffres'] ?? 0;

        if (empty($offers)) {
            $this->warn("Page vide reçue. Réinitialisation du cycle à la page 1.");
            Cache::put('forem_scan_cycle_page', 1);
            return;
        }

        $this->importResults($offers);

        // Calculer la page suivante
        if (($currentPage * $rowsPerPage) >= $total) {
            $this->info("Fin du cycle atteinte ({$total} offres). Réinitialisation à la page 1.");
            Cache::put('forem_scan_cycle_page', 1);
        } else {
            $nextPage = $currentPage + 1;
            $this->info("Page {$currentPage} terminée. Prochaine page : {$nextPage}.");
            Cache::put('forem_scan_cycle_page', $nextPage);
        }
    }

    protected function importResults(array $offers)
    {
        $bar = $this->output->createProgressBar(count($offers));
        $bar->start();

        foreach ($offers as $item) {
            $this->jobOfferService->saveBasicOffer($item);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info(count($offers) . " offres traitées.");
    }

    protected function cleanupOldOffers()
    {
        $this->info("Archivage des offres non vues depuis 24h...");
        $count = JobOffer::where('status', 'active')
            ->where('last_seen_at', '<', now()->subHours(24))
            ->update(['status' => 'archived']);
            
        if ($count > 0) {
            $this->warn("{$count} offres ont été archivées.");
        }
    }
}
