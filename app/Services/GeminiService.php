<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected ?string $apiKey;
    protected string $model;
    protected array $configModels;
    protected ?array $lastUsage = null;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', env('GEMINI_API_KEY')) ?? '';
        $this->configModels = config('services.gemini.models', []);
        $this->model = $this->configModels['chat'] ?? 'gemini-3.1-flash-lite-preview';
    }

    /**
     * Définit le modèle à utiliser pour la prochaine requête.
     */
    public function withModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function withConfigModel(string $type): self
    {
        $this->model = $this->configModels[$type] ?? 'gemini-2.5-flash-lite';
        return $this;
    }

    protected function getUrl(): string
    {
        return "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";
    }

    public function getLastUsage(): ?array
    {
        return $this->lastUsage;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function log(string $category, ?int $userId = null, ?array $manualUsage = null): void
    {
        $userId = $userId ?? auth()->id();
        
        // Fallback pour les tâches d'arrière-plan (ex: vectorisation d'offres en file d'attente)
        // On attribue l'appel au premier administrateur trouvé si aucun utilisateur n'est connecté.
        if (!$userId) {
            $userId = \App\Models\User::where('is_admin', true)->first()?->id;
        }

        if (!$userId) return;

        $usage = $manualUsage ?? $this->lastUsage;
        if (!$usage) return;

        \App\Models\AiLog::create([
            'user_id' => $userId,
            'model' => $usage['model'] ?? $this->model,
            'category' => $category,
            'tokens_in' => $usage['promptTokenCount'] ?? ($usage['tokens_in'] ?? 0),
            'tokens_out' => $usage['candidatesTokenCount'] ?? ($usage['tokens_out'] ?? 0),
        ]);
    }

    public function analyzeMatch(string $prompt): ?array
    {
        return $this->withConfigModel('match')->generateJson($prompt);
    }

    /**
     * Envoie un prompt à Gemini et retourne la réponse sous forme de texte brut.
     */
    public function ask(string $prompt): ?string
    {
        $payload = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'temperature' => 1.0,
                'maxOutputTokens' => 4096,
            ]
        ];

        Log::debug('GEMINI ASK REQUEST:', ['model' => $this->model, 'payload' => $payload]);

        $response = Http::withoutVerifying()
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(25)
            ->retry(3, 1000, function ($exception, $request) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException ||
                       ($exception->response && $exception->response->status() === 503);
            })
            ->post("{$this->getUrl()}?key={$this->apiKey}", $payload);

        if ($response->failed()) {
            Log::error('Gemini API request failed (ask) after retries', ['model' => $this->model, 'error' => $response->body()]);
            return null;
        }

        $result = $response->json();
        Log::debug('GEMINI ASK RESPONSE:', ['model' => $this->model, 'result' => $result]);
        
        $this->lastUsage = $result['usageMetadata'] ?? null;
        
        return $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
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
        4. Liste les compétences techniques et soft skills principales identifiées. Utilise des termes standards du marché de l'emploi (référentiel ROME/Forem) pour faciliter le matching.
        
        Réponds UNIQUEMENT en JSON avec la structure suivante :
        {
            \"headline\": \"string\",
            \"profile_text\": \"string\",
            \"aspirations\": \"string\",
            \"skills\": [\"Nom de la compétence\", ...]
        }
        ";

        return $this->withConfigModel('chat')->generateJson($prompt);
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

        return $this->withConfigModel('chat')->generateJson($prompt);
    }

    public function chat(array $messages, ?string $systemInstruction = null, ?array $responseSchema = null): ?array
    {
        return $this->executeRequest($messages, $systemInstruction, $responseSchema);
    }

    /**
     * Méthode générique pour générer du JSON à partir d'un simple prompt.
     */
    public function generateJson(string $prompt, ?string $systemInstruction = null): ?array
    {
        $messages = [
            ['role' => 'user', 'parts' => [['text' => $prompt]]]
        ];

        return $this->executeRequest($messages, $systemInstruction);
    }

    protected function executeRequest(array $messages, ?string $systemInstruction = null, ?array $responseSchema = null): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('Gemini API key is missing.');
            return null;
        }

        $payload = [
            'contents' => $messages,
            'generationConfig' => [
                'temperature' => 1.0,
                'maxOutputTokens' => 4096,
                'responseMimeType' => 'application/json',
            ]
        ];

        if ($responseSchema) {
            $payload['generationConfig']['responseJsonSchema'] = $responseSchema;
        }

        if ($systemInstruction) {
            $payload['system_instruction'] = [
                'parts' => [['text' => $systemInstruction]]
            ];
        }

        Log::debug('GEMINI REQUEST:', ['model' => $this->model, 'payload' => $payload]);

        $response = Http::withoutVerifying()
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(35)
            ->retry(3, 1000, function ($exception, $request) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException ||
                       ($exception->response && $exception->response->status() === 503);
            })
            ->post("{$this->getUrl()}?key={$this->apiKey}", $payload);

        if ($response->failed()) {
            Log::error('Gemini API failed', ['model' => $this->model, 'error' => $response->body()]);
            return null;
        }

        $result = $response->json();
        Log::debug('GEMINI RESPONSE:', ['model' => $this->model, 'result' => $result]);

        $this->lastUsage = $result['usageMetadata'] ?? null;

        $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!$text) return null;

        $decoded = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Gemini JSON Error', ['error' => json_last_error_msg()]);
            return null;
        }

        if (isset($decoded['data'])) $decoded = $decoded['data'];
        elseif (isset($decoded['result'])) $decoded = $decoded['result'];

        return $decoded;
    }

    /**
     * Effectue un OCR sur un fichier (image ou PDF scanné) via Gemini.
     */
    public function ocr(string $filePath, string $mimeType): ?string
    {
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => "Extrais tout le texte de ce document (CV) de manière brute et fidèle. Si c'est une image, fais un OCR précis. Ne réponds que par le texte extrait du document, sans ajouter de commentaires de ta part."],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => base64_encode(file_get_contents($filePath))
                            ]
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
            ]
        ];

        $this->withConfigModel('ocr');
        $originalModel = $this->model;

        Log::info('Gemini OCR Request:', ['mime' => $mimeType, 'path' => $filePath, 'model' => $originalModel]);

        $response = Http::withoutVerifying()
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(45) // L'OCR est plus long (upload fichier), on laisse 45s
            ->post("{$this->getUrl()}?key={$this->apiKey}", $payload);

        if ($response->status() === 503) {
            throw new \Exception("Les serveurs AI sont actuellement surchargés (Erreur 503). Veuillez réessayer dans quelques instants.");
        }

        if ($response->failed()) {
            Log::error('Gemini OCR API error', ['model' => $this->model, 'status' => $response->status(), 'body' => $response->body()]);
            return null;
        }

        $result = $response->json();
        $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

        $this->lastUsage = $result['usageMetadata'] ?? null;

        Log::info('Gemini OCR Result:', ['model' => $this->model, 'char_count' => strlen($text ?? '')]);

        return $text;
    }

    /**
     * Génère un vecteur (embedding) pour un texte donné.
     * Utilise le modèle gemini-embedding-001.
     */
    public function embed(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('Gemini API key is missing.');
            return null;
        }

        // On vérifie que le texte n'est pas vide ou trop court (boilerplate seul)
        if (strlen(trim($text)) < 50) {
            Log::warning('GEMINI EMBED SKIPPED: Text too short or empty', ['text' => $text]);
            return null;
        }

        $payload = [
            'model' => 'models/gemini-embedding-001',
            'taskType' => $taskType,
            'content' => [
                'parts' => [['text' => $text]]
            ],
            'outputDimensionality' => 768
        ];

        Log::info('GEMINI EMBED REQUEST', [
            'task' => $taskType,
            'text' => $text,
            'char_count' => strlen($text)
        ]);

        $response = Http::withoutVerifying()
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(30)
            ->retry(3, 1000) // 3 tentatives, 1s d'intervalle entre chaque
            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent?key={$this->apiKey}", $payload);

        if ($response->failed()) {
            Log::error('GEMINI EMBED FAILED', [
                'status' => $response->status(),
                'error' => $response->body()
            ]);
            return null;
        }

        $result = $response->json();
        $embedding = $result['embedding']['values'] ?? null;

        if ($embedding) {
            // Log de l'appel vectoriel (estimation des tokens car non fournis par l'API embedContent)
            $this->log('vector', null, [
                'model' => 'gemini-embedding-001',
                'promptTokenCount' => ceil(strlen($text) / 4),
                'candidatesTokenCount' => 0
            ]);
        }

        Log::info('GEMINI EMBED RESPONSE', [
            'status' => $response->status(),
            'vector_size' => $embedding ? count($embedding) : 0
        ]);

        return $embedding;
    }
}
