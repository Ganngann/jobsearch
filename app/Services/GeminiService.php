<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected ?string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', env('GEMINI_API_KEY')) ?? '';
    }

    public function analyzeMatch(string $prompt): ?array
    {
        return $this->generateJson($prompt);
    }

    /**
     * Analyse un CV ou un texte brut pour en extraire un profil structuré.
     */
    public function analyzeProfile(string $text): ?array
    {
        $prompt = "
        Tu es un expert en recrutement. Analyse le texte suivant (CV ou bio) et extrais-en un profil structuré.
        
        ## Texte à analyser
        {$text}

        ## Instructions
        1. Génère un 'headline' (titre pro percutant).
        2. Rédige un 'profile_text' (récit narratif de la dimension humaine, environ 150-200 mots).
        3. Identifie les 'aspirations' (valeurs et ce que le candidat recherche).
        4. Liste les compétences techniques et soft skills principales identifiées.
        
        Réponds UNIQUEMENT en JSON avec la structure suivante :
        {
            \"headline\": \"string\",
            \"profile_text\": \"string\",
            \"aspirations\": \"string\",
            \"skills\": [\"Nom de la compétence\", ...]
        }
        ";

        return $this->generateJson($prompt);
    }

    public function chat(array $messages, ?string $systemInstruction = null): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('Gemini API key is missing.');
            return null;
        }

        $payload = [
            'contents' => $messages,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2048,
                'responseMimeType' => 'application/json',
            ]
        ];

        if ($systemInstruction) {
            $payload['system_instruction'] = [
                'parts' => [['text' => $systemInstruction]]
            ];
        }

        // DEBUG: On log la requête pour voir ce qui est envoyé à l'IA
        Log::debug('GEMINI REQUEST PAYLOAD:', $payload);

        $response = Http::withoutVerifying()->withHeaders([
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}?key={$this->apiKey}", $payload);

        if ($response->failed()) {
            Log::error('Gemini API request failed', ['error' => $response->body()]);
            return null;
        }

        $result = $response->json();
        
        // Log de la réponse reçue de Gemini
        Log::debug('GEMINI RESPONSE:', $result);

        $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

        return $text ? json_decode($text, true) : null;
    }

    /**
     * Méthode générique pour appeler Gemini et obtenir du JSON.
     */
    protected function generateJson(string $prompt): ?array
    {
        return $this->chat([
            ['role' => 'user', 'parts' => [['text' => $prompt]]]
        ]);
    }
}
