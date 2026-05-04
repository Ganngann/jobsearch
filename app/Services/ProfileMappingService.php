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
        // 1. On récupère tous les faits
        $facts = $user->facts()->get();
        if ($facts->isEmpty()) {
            return ['success' => false, 'message' => 'Aucun récit trouvé à mapper.'];
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
        $blacklistedIds = $user->blacklistedSkills()->pluck('skills.id');
        
        $query = Skill::query()
            ->whereNotIn('id', $blacklistedIds);

        $query->where(function($q) use ($keywords) {
            foreach ($keywords as $kw) {
                $q->orWhere('label', 'LIKE', '%' . $kw . '%');
            }
        });

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
                    // On les ajoute en 'neutral' par défaut pour qu'elles apparaissent dans le flux de validation
                    // si elles ne sont pas déjà présentes.
                    $user->skills()->syncWithoutDetaching([
                        $skillId => [
                            'level' => 'intermediate',
                            'status' => 'neutral' // Par défaut on les met en neutre pour validation
                        ]
                    ]);
                }
            }
        }

        return $linksCreated;
    }

    /**
     * Suggère 20 compétences pertinentes basées sur le récit et la popularité sur le marché.
     */
    public function suggestSkills(User $user, int $count = 20): array
    {
        // 1. On récupère les compétences déjà connues (actives, neutres ou refusées)
        $knownSkillIds = DB::table('user_skill')
            ->where('user_id', $user->id)
            ->pluck('skill_id')
            ->toArray();

        // 2. On récupère le contexte narratif
        $facts = $user->facts()->pluck('content')->implode("\n");

        // 3. On demande à l'IA d'extraire des concepts de compétences basés sur le récit
        $prompt = "
        Tu es un expert en orientation. Analyse ce récit de vie et extrais 30 concepts de compétences (hard ou soft) qui y sont suggérés.
        
        RÉCIT :
        {$facts}
        
        Réponds uniquement en JSON : { \"concepts\": [\"Gestion de projet\", \"Négociation\", ...] }
        ";

        $aiResponse = $this->gemini->withConfigModel('gemini-2.5-flash-lite')->generateJson($prompt);
        $concepts = $aiResponse['concepts'] ?? [];

        if (empty($concepts)) return [];

        // 4. On cherche ces concepts dans notre base, en privilégiant la popularité (fréquence dans les offres)
        $suggestedSkills = Skill::query()
            ->whereNotIn('id', $knownSkillIds)
            ->where(function($q) use ($concepts) {
                foreach (array_slice($concepts, 0, 15) as $c) { // On prend les 15 premiers concepts IA
                    $q->orWhere('label', 'LIKE', '%' . $c . '%');
                }
            })
            ->withCount('jobOffers') // Calcul de la popularité
            ->orderBy('job_offers_count', 'desc')
            ->limit($count)
            ->get();

        // Si on n'en a pas assez, on complète par des compétences transversales populaires
        if ($suggestedSkills->count() < $count) {
            $extraCount = $count - $suggestedSkills->count();
            $extra = Skill::query()
                ->whereNotIn('id', $knownSkillIds)
                ->whereNotIn('id', $suggestedSkills->pluck('id'))
                ->withCount('jobOffers')
                ->orderBy('job_offers_count', 'desc')
                ->limit($extraCount)
                ->get();
            
            $suggestedSkills = $suggestedSkills->concat($extra);
        }

        return $suggestedSkills->map(function($s) {
            return [
                'id' => $s->id,
                'label' => $s->label,
                'popularity' => $s->job_offers_count,
                'type' => $s->type
            ];
        })->toArray();
    }
}
