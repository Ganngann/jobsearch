<?php

namespace App\Services;

use App\Models\User;
use App\Models\Skill;
use App\Models\UserFact;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProfileMappingService
{
    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Mappe les faits narratifs de l'utilisateur aux compétences normalisées du Forem de manière granulaire.
     */
    public function mapUserFacts(User $user): array
    {
        // 1. On récupère tous les faits validés
        $facts = $user->facts()->where('status', 'validated')->get();
        if ($facts->isEmpty()) {
            return ['success' => false, 'message' => 'Aucun fait validé à mapper.'];
        }

        $totalLinks = 0;
        $mappings = [];

        // 2. On traite par petits lots de 10 faits pour garder une précision maximale
        foreach ($facts->chunk(10) as $chunk) {
            $chunkLinks = $this->processChunk($user, $chunk);
            $totalLinks += $chunkLinks;
        }

        return [
            'success' => true, 
            'message' => "Analyse terminée : {$totalLinks} liens créés entre vos récits et la taxonomie Forem.",
        ];
    }

    protected function processChunk(User $user, $facts): int
    {
        $linksCreated = 0;
        
        // A. On demande à l'IA d'extraire des termes de recherche pour ces 10 faits
        $factsText = $facts->map(fn($f) => "- {$f->content}")->implode("\n");
        
        $promptKeywords = "
        Analyse ces 10 expériences professionnelles et extrais-en une liste de mots-clés techniques ou métiers.
        
        RÈGLES IMPORTANTES :
        1. Utilise uniquement des MOTS UNIQUES (pas de suites de mots, pas d'espaces). 
           Exemple : Au lieu de 'Gestion équipe', utilise 'Management' ou 'Équipe'. 
           Au lieu de 'Artisan boulanger', utilise 'Boulanger'.
        2. Ces mots serviront de termes de recherche SQL.
        
        EXPÉRIENCES :
        {$factsText}
        
        Réponds uniquement au format JSON : { \"keywords\": [\"Mot1\", \"Mot2\", ...] }
        ";

        $kwResponse = $this->gemini->chat([['role' => 'user', 'parts' => [['text' => $promptKeywords]]]]);
        $keywords = $kwResponse['keywords'] ?? [];

        if (empty($keywords)) return 0;

        // B. Recherche SQL des compétences candidates (on cherche les termes dans les labels)
        $query = Skill::query();
        foreach ($keywords as $kw) {
            $query->orWhere('label', 'LIKE', '%' . $kw . '%');
        }
        $candidates = $query->limit(50)->get(['id', 'label']); // On limite pour ne pas saturer l'IA

        if ($candidates->isEmpty()) return 0;

        // C. On demande à l'IA de faire le mapping final entre les 10 faits et les 50 candidats
        $factsContext = $facts->map(fn($f) => "[FACT_ID:{$f->id}] {$f->content}")->implode("\n");
        $skillsContext = $candidates->map(fn($s) => "[SKILL_ID:{$s->id}] {$s->label}")->implode("\n");

        $promptMapping = "
        Lien entre récits et taxonomie Forem.
        
        FAITS UTILISATEUR :
        {$factsContext}
        
        COMPÉTENCES FOREM CANDIDATES :
        {$skillsContext}
        
        INSTRUCTIONS :
        Pour chaque FAIT, indique les SKILL_ID qui correspondent exactement. 
        Sois rigoureux. Si aucun ne correspond pour un fait, n'en mets pas.
        
        Réponds uniquement au format JSON : 
        { \"links\": [ { \"fact_id\": 1, \"skill_ids\": [12, 45] }, ... ] }
        ";

        $mapResponse = $this->gemini->chat([['role' => 'user', 'parts' => [['text' => $promptMapping]]]]);
        
        if (isset($mapResponse['links'])) {
            $allSkillIds = [];
            foreach ($mapResponse['links'] as $link) {
                $fact = UserFact::find($link['fact_id']);
                if ($fact && $fact->user_id === $user->id) {
                    $fact->skills()->sync($link['skill_ids'], false);
                    $linksCreated += count($link['skill_ids']);
                    $allSkillIds = array_merge($allSkillIds, $link['skill_ids']);
                }
            }

            // D. On ajoute ces compétences au profil GLOBAL de l'utilisateur s'il ne les a pas déjà
            if (!empty($allSkillIds)) {
                $uniqueSkillIds = array_unique($allSkillIds);
                foreach ($uniqueSkillIds as $skillId) {
                    // On utilise syncWithoutDetaching pour ne pas écraser les compétences existantes 
                    // et on met un niveau par défaut 'intermediate'
                    $user->skills()->syncWithoutDetaching([
                        $skillId => ['level' => 'intermediate']
                    ]);
                }
            }
        }

        return $linksCreated;
    }
}
