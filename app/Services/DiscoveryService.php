<?php

namespace App\Services;

use App\Models\User;
use App\Models\ReferentielMetier;
use Illuminate\Support\Facades\Log;

class DiscoveryService
{
    protected $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    public function suggestMetiers(User $user)
    {
        $profile = $this->formatUserProfile($user);
        $referentiel = ReferentielMetier::where('is_active', true)->get(['code', 'title', 'family_name']);
        
        $referentielList = $referentiel->map(function($item) {
            return "[{$item->code}] {$item->title} ({$item->family_name})";
        })->implode("\n");

        $prompt = "Tu es un expert en orientation professionnelle spécialisé dans le 'Matching Humain'.
Ton but est de proposer à l'utilisateur des métiers issus du référentiel ROME qui correspondent à sa personnalité, ses aspirations et ses compétences, même si ce ne sont pas ses métiers habituels.

PROFIL UTILISATEUR :
{$profile}

RÉFÉRENTIEL ROME DISPONIBLE :
{$referentielList}

CONSIGNES :
1. Analyse le profil en profondeur (ne t'arrête pas aux titres de postes passés, regarde les traits de caractère et les aspirations).
2. Choisis exactement 3 codes ROME dans la liste fournie.
3. Sois audacieux : propose au moins un métier 'surprise' qui exploite un talent caché ou une aspiration latente de l'utilisateur.
4. Pour chaque métier, fournis une justification courte (2 phrases max) très personnalisée.

RÉPONSES AU FORMAT JSON UNIQUEMENT :
{
    \"suggestions\": [
        {
            \"code\": \"M1805\",
            \"title\": \"Développement informatique\",
            \"reason\": \"Ta passion pour la résolution de problèmes complexes et ton autonomie...\",
            \"type\": \"aligned|surprise\"
        },
        ...
    ]
}";

        Log::debug("DISCOVERY: Generated prompt for user {$user->id}");

        try {
            $response = $this->gemini->ask($prompt);
            
            if (!$response) {
                Log::warning("DISCOVERY: Empty response from Gemini");
                return [];
            }

            // Nettoyage de la réponse au cas où Gemini ajoute des balises ```json
            $json = preg_replace('/^```json\s*|```$/', '', trim($response));
            $data = json_decode($json, true);

            if (!$data || !isset($data['suggestions'])) {
                Log::error("DISCOVERY: Invalid JSON format", ['raw' => $response]);
                return [];
            }

            Log::debug("DISCOVERY: Successfully parsed " . count($data['suggestions']) . " suggestions");

            return $data['suggestions'];
        } catch (\Exception $e) {
            Log::error("Error in DiscoveryService", ['message' => $e->getMessage()]);
            return [];
        }
    }

    protected function formatUserProfile(User $user)
    {
        $skills = $user->skills->pluck('name')->implode(', ');
        $aspirations = $user->aspirations;
        $profileText = $user->profile_text;
        $headline = $user->headline;

        return "Titre : {$headline}\nDescription : {$profileText}\nAspirations : {$aspirations}\nCompétences : {$skills}";
    }
}
