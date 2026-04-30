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
    public function searchJobs(int $page = 1, int $rows = 20): array
    {
        $url = "{$this->baseUrl}/Recherches/Search";
        
        try {
            $response = Http::withHeaders(['User-Agent' => $this->userAgent])
                ->get($url, [
                    'page' => $page,
                    'row' => $rows,
                    'sort' => 'DatePublication',
                ]);

            if ($response->failed()) {
                Log::error("Forem Search API Error: {$response->status()}", ['body' => $response->body()]);
                return [];
            }

            return $response->json();
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
            $response = Http::withHeaders(['User-Agent' => $this->userAgent])
                ->get($url);

            if ($response->failed()) {
                Log::error("Forem Detail API Error for ID {$jobId}: {$response->status()}");
                return [];
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Forem Detail API Exception for ID {$jobId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Facettes / Taxonomies (Criteres API)
     */
    public function getTaxonomies(): array
    {
        $url = "{$this->baseUrl}/Recherches/SearchNombreParCritere";
        
        try {
            $response = Http::withHeaders(['User-Agent' => $this->userAgent])
                ->get($url);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Forem Taxonomies API Exception: " . $e->getMessage());
            return [];
        }
    }
}
