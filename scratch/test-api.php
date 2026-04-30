<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;

$client = new Client(['verify' => false]);

try {
    echo "Testing Forem Search API with EXACT PAYLOAD...\n";
    $response = $client->post('https://www.leforem.be/recherche-offres/api/Recherches/Search?page=1&row=5', [
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json',
            'Referer' => 'https://www.leforem.be/recherche-offres/offres',
        ],
        'json' => [
            'filtres' => [],
            'filtresCodifies' => [],
            'locutions' => [],
            'metier' => [],
            'operateurLocutions' => 'ET',
            'priority' => 1,
            'secteur' => [],
        ]
    ]);
    
    echo "Status: " . $response->getStatusCode() . "\n";
    $body = (string)$response->getBody();
    echo "Body Snippet: " . substr($body, 0, 1000) . "...\n";
    
    $data = json_decode($body, true);
    if (isset($data['offres'])) {
        echo "Found 'offres' key with " . count($data['offres']) . " items.\n";
    } elseif (isset($data['resultats'])) {
        echo "Found 'resultats' key with " . count($data['resultats']) . " items.\n";
    } else {
        echo "Keys found in response: " . implode(', ', array_keys($data)) . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
