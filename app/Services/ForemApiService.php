<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ForemApiService
{
    protected string $baseUrl = 'https://api.leforem.be/v1'; // Hypothétique, à ajuster si nécessaire

    /**
     * Simulation de la recherche d'offres (Search API)
     * En production, cela appellerait l'endpoint réel.
     */
    public function searchJobs(array $criteria = []): array
    {
        // Pour le MVP, on simule ou on lit le fichier exemple si l'API n'est pas dispo
        // $response = Http::get("{$this->baseUrl}/jobs/search", $criteria);
        // return $response->json();

        $path = base_path('docs/api-examples/search-results.json');
        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true);
        }

        return [];
    }

    /**
     * Simulation du détail d'une offre (Detail API)
     */
    public function getJobDetail(int $jobId): array
    {
        // $response = Http::get("{$this->baseUrl}/jobs/{$jobId}");
        // return $response->json();

        $path = base_path('docs/api-examples/job-detail.json');
        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true);
        }

        return [];
    }

    /**
     * Simulation des facettes/taxonomies (Facettes API)
     */
    public function getTaxonomies(): array
    {
        $path = base_path('docs/api-examples/search-by-criteria.json');
        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true);
        }

        return [];
    }
}
