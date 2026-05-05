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

        // 2. On récupère le catalogue SANS les compétences déjà triées et AVEC au moins DEUX offres actives
        $allSkills = Skill::select('id', 'label')
            ->whereNotIn('id', $knownSkillIds)
            ->whereHas('jobOffers', function($query) {
                $query->where('status', 'active');
            }, '>=', 2)
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

        // 5. On récupère les compétences par ID (en s'assurant qu'elles respectent toujours les critères)
        $suggestedSkills = Skill::query()
            ->whereIn('id', $selectedIds)
            ->whereNotIn('id', $knownSkillIds)
            ->whereHas('jobOffers', function($query) {
                $query->where('status', 'active');
            }, '>=', 2)
            ->withCount(['jobOffers' => function($query) {
                $query->where('status', 'active');
            }])
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
