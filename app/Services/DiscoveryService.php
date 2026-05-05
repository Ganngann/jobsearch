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
        
        $excludedCodes = $user->preferredReferentielMetiers()->pluck('code')->toArray();
        $excludedCodesString = !empty($excludedCodes) ? implode(', ', $excludedCodes) : 'Aucun';

        $referentielList = $referentiel->map(function($item) {
            return "[{$item->code}] {$item->title} ({$item->family_name})";
        })->implode("\n");

        $prompt = "Tu es un expert en orientation professionnelle spécialisé dans le 'Matching Humain'.
Ton but est de proposer à l'utilisateur des métiers issus du référentiel ROME qui correspondent à sa personnalité, ses aspirations et ses compétences.

PROFIL UTILISATEUR :
{$profile}

RÉFÉRENTIEL ROME DISPONIBLE :
{$referentielList}

CONSIGNES CRITIQUES :
1. Tu dois proposer exactement 12 métiers.
2. NE SUGGÈRE PAS les codes suivants (déjà favoris ou blacklistés) : {$excludedCodesString}.
3. Propose un mix équilibré :
   - 6 métiers 'aligned' : proches de son profil actuel.
   - 6 métiers 'surprise' : inattendus mais cohérents avec ses soft skills et aspirations profondes.
4. Pour chaque métier, fournis une justification courte (2 phrases max) très personnalisée.

RÉPONSES AU FORMAT JSON UNIQUEMENT :
{
    \"suggestions\": [
        {
            \"code\": \"M1805\",
            \"title\": \"Développement informatique\",
            \"reason\": \"...\",
            \"type\": \"aligned|surprise\"
        }
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
        $skills = $user->skills->pluck('label')->implode(', ');
        $aspirations = $user->aspirations;
        $profileText = $user->profile_text;
        $headline = $user->headline;
        $facts = $user->facts->pluck('content')->implode("\n- ");

        return "Titre : {$headline}\nBio : {$profileText}\nAspirations : {$aspirations}\nCompétences : {$skills}\nRécits/Expériences :\n- {$facts}";
    }
}
