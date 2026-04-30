<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ForemApiService
{
    protected string $baseUrl = 'https://www.leforem.be/recherche-offres/api';
    protected string $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    /**
     * Recherche d'offres (Search API)
     */
    public function searchJobs(array $criteria = [], int $page = 1, int $rows = 20): array
    {
        $url = "{$this->baseUrl}/Recherches/Search?page={$page}&row={$rows}";
        
        $payload = array_merge([
            'filtres' => [],
            'filtresCodifies' => [],
            'locutions' => [],
            'metier' => [],
            'operateurLocutions' => 'ET',
            'priority' => 1,
            'secteur' => [],
        ], $criteria);

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'User-Agent' => $this->userAgent,
                    'Accept' => 'application/json, text/plain, */*',
                    'Content-Type' => 'application/json',
                    'Referer' => 'https://www.leforem.be/recherche-offres/offres',
                    'Origin' => 'https://www.leforem.be',
                ])->post($url, $payload);

            if ($response->failed()) {
                Log::error("Forem Search API Error: {$response->status()}", ['body' => $response->body()]);
                return [];
            }

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error("Forem Search API Exception: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Détail d'une offre (Detail API)
     */
    public function getJobDetail(int $jobId): array
    {
        $url = "{$this->baseUrl}/Diffusion/DetailOffre/{$jobId}";
        
        try {
            $response = Http::withoutVerifying() // Correction SSL
                ->withHeaders([
                    'User-Agent' => $this->userAgent,
                    'Accept' => 'application/json, text/plain, */*',
                    'Referer' => "https://www.leforem.be/recherche-offres/detail-offre/{$jobId}",
                ])->get($url);

            if ($response->failed()) {
                Log::error("Forem Detail API Error for ID {$jobId}: {$response->status()}");
                return [];
            }

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error("Forem Detail API Exception for ID {$jobId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Facettes / Taxonomies (Criteres API)
     */
    public function getTaxonomies(array $criteria = []): array
    {
        $url = "{$this->baseUrl}/Recherches/SearchNombreParCritere?page=1&row=10";
        
        $payload = array_merge([
            'filtres' => [],
            'filtresCodifies' => [],
            'locutions' => [],
            'metier' => [],
            'operateurLocutions' => 'ET',
            'priority' => 1,
            'secteur' => [],
        ], $criteria);

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'User-Agent' => $this->userAgent,
                    'Accept' => 'application/json, text/plain, */*',
                    'Content-Type' => 'application/json',
                    'Referer' => 'https://www.leforem.be/recherche-offres/offres',
                ])->post($url, $payload);

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error("Forem Taxonomies API Exception: " . $e->getMessage());
            return [];
        }
    }
}
