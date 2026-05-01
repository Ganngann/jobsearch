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

    /**
     * Génère une synthèse de profil à partir d'une liste de faits validés.
     */
    public function generateProfileFromFacts(array $facts): ?array
    {
        $factsText = collect($facts)->pluck('content')->implode("\n- ");
        
        $prompt = "
        Tu es un expert en personal branding. À partir des 'faits' suivants extraits du parcours d'un candidat, rédige les éléments clés de son profil.
        
        ## Faits validés
        - {$factsText}

        ## Instructions
        1. Génère un 'headline' (titre pro percutant et moderne).
        2. Rédige un 'profile_text' (récit narratif captivant de la dimension humaine, environ 200 mots). Parle de la personne à la 3ème personne ou à la 1ère personne de manière élégante.
        3. Synthétise les 'aspirations' (ce que le candidat recherche et ses valeurs).
        
        Réponds UNIQUEMENT en JSON avec la structure suivante :
        {
            \"headline\": \"string\",
            \"profile_text\": \"string\",
            \"aspirations\": \"string\"
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
                'maxOutputTokens' => 4096,
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

        if (!$text) return null;

        $decoded = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Gemini JSON Decode Error: ' . json_last_error_msg());
            return [
                'reply' => "Oups, ma réponse était tellement détaillée qu'elle a été coupée en plein milieu ! Pouvons-nous reprendre en traitant les points un par un ?",
                'facts' => []
            ];
        }

        return $decoded;
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
