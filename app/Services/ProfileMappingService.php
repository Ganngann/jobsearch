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
    /**
     * Suggère des compétences pertinentes en faisant matcher le profil avec le catalogue réel via l'IA.
     */
    /**
     * Suggère des compétences pertinentes en faisant matcher le profil avec le catalogue réel via l'IA.
     */
    public function suggestSkills(User $user, int $count = 30): array
    {
        // 1. On récupère les compétences déjà connues pour les exclure
        $knownSkillIds = DB::table('user_skill')
            ->where('user_id', $user->id)
            ->pluck('skill_id')
            ->toArray();

        // 2. On récupère le catalogue SANS les compétences déjà triées et AVEC au moins une offre
        $allSkills = Skill::select('id', 'label')
            ->whereNotIn('id', $knownSkillIds)
            ->has('jobOffers')
            ->get();
        
        $skillsCatalog = $allSkills->map(fn($s) => "[ID:{$s->id}] {$s->label}")->implode("\n");

        // 3. On récupère le contexte du profil
        $experiences = $user->experiences()->get()->map(fn($e) => "- Poste: {$e->title} ({$e->company}). Mission: {$e->description}")->implode("\n");
        $educations = $user->educations()->get()->map(fn($e) => "- Diplôme: {$e->degree} ({$e->school})")->implode("\n");
        $facts = $user->facts()->pluck('content')->implode("\n- ");
        
        $fullContext = "### PARCOURS PRO\n{$experiences}\n\n### FORMATIONS\n{$educations}\n\n### RÉCIT\n{$facts}";

        // 4. Prompt de sélection par ID (robuste)
        $prompt = "
        Tu es un expert en recrutement. Ton but est de sélectionner les compétences les plus pertinentes pour un candidat en piochant UNIQUEMENT dans le CATALOGUE OFFICIEL fourni.

        CATALOGUE OFFICIEL (Format: [ID] Libellé) :
        {$skillsCatalog}

        PARCOURS DU CANDIDAT :
        {$fullContext}

        CONSIGNES :
        1. Sélectionne entre 20 et 40 compétences du CATALOGUE qui sont réellement démontrées par le parcours.
        2. Ne propose QUE des IDs présents dans le catalogue.
        3. Privilégie la précision.

        Réponds UNIQUEMENT en JSON : { 
            \"suggestions\": [
                { \"id\": ID1, \"reason\": \"Courte explication de 5-6 mots max\" },
                ...
            ]
        }
        ";

        $aiResponse = $this->gemini->withModel('gemini-2.0-flash-lite')->generateJson($prompt);
        $suggestionsData = $aiResponse['suggestions'] ?? [];
        $selectedIds = collect($suggestionsData)->pluck('id')->toArray();

        if (empty($selectedIds)) return [];

        // 5. On récupère les compétences par ID
        $suggestedSkills = Skill::query()
            ->whereIn('id', $selectedIds)
            ->whereNotIn('id', $knownSkillIds)
            ->withCount('jobOffers')
            ->get();

        // On remap pour inclure la raison de l'IA
        $reasons = collect($suggestionsData)->pluck('reason', 'id');

        return $suggestedSkills->map(function($s) use ($reasons) {
            return [
                'id' => $s->id,
                'label' => $s->label,
                'popularity' => $s->job_offers_count,
                'type' => $s->type,
                'reason' => $reasons[$s->id] ?? "Basé sur votre profil"
            ];
        })->sortByDesc('popularity')->values()->toArray();
    }
}
