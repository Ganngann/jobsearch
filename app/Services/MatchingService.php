<?php

namespace App\Services;

use App\Models\JobOffer;
use App\Models\User;
use App\Models\UserMatch;
use Illuminate\Support\Facades\Log;

class MatchingService
{
    protected $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Calcule le score de correspondance complet (Pre-score + IA si nécessaire).
     */
    public function match(User $user, JobOffer $jobOffer, bool $forceAi = false): UserMatch
    {
        // 1. Layer 1 — Pré-score (Statique)
        $preScore = $this->calculatePreScore($user, $jobOffer);

        $match = UserMatch::updateOrCreate(
            ['user_id' => $user->id, 'job_offer_id' => $jobOffer->id],
            ['pre_score' => $preScore]
        );

        // 2. Layer 2 — Analyse IA
        // On lance l'IA si :
        // - On force l'analyse (demande manuelle)
        // - OU (Le pre-score est élevé ET pas encore d'analyse faite)
        if ($forceAi || ($preScore >= 30 && !$match->analyzed_at)) {
            $this->performAiAnalysis($user, $jobOffer, $match);
        }

        return $match;
    }

    /**
     * Calcule un score rapide basé sur les compétences, langues et permis.
     */
    public function calculatePreScore(User $user, JobOffer $jobOffer): int
    {
        $score = 0;

        // Compétences (35% Hard + 15% Soft = 50% max)
        $userSkillIds = $user->skills()->pluck('skills.id')->toArray();
        $requiredSkills = $jobOffer->skills;

        if ($requiredSkills->count() > 0) {
            $matchedCount = $requiredSkills->whereIn('id', $userSkillIds)->count();
            $score += ($matchedCount / $requiredSkills->count()) * 50;
        }

        // Langues (10%)
        $userLangIds = $user->languages()->pluck('languages.id')->toArray();
        $requiredLangs = $jobOffer->languages;
        if ($requiredLangs->count() > 0) {
            $matchedLangs = $requiredLangs->whereIn('id', $userLangIds)->count();
            $score += ($matchedLangs / $requiredLangs->count()) * 10;
        }

        // Permis (Bonus/Filtrage simple)
        $userPermitIds = $user->permits()->pluck('permits.id')->toArray();
        $requiredPermits = $jobOffer->permits;
        if ($requiredPermits->count() > 0) {
            $hasAllPermits = $requiredPermits->whereNotIn('id', $userPermitIds)->isEmpty();
            if ($hasAllPermits) $score += 10;
        }

        return (int) min(100, $score);
    }

    /**
     * Effectue l'analyse sémantique avec Gemini.
     */
    public function performAiAnalysis(User $user, JobOffer $jobOffer, UserMatch $match): bool
    {
        $prompt = $this->buildPrompt($user, $jobOffer);
        $result = $this->gemini->analyzeMatch($prompt);

        if ($result) {
            $match->update([
                'ai_score' => $result['score'] ?? null,
                'final_score' => $result['score'] ?? $match->pre_score,
                'strengths' => $result['points_forts'] ?? [],
                'weaknesses' => $result['points_faibles'] ?? [],
                'ai_raw_response' => $result,
                'analyzed_at' => now(),
            ]);
            return true;
        }

        return false;
    }

    /**
     * Construit le prompt pour l'IA.
     */
    protected function buildPrompt(User $user, JobOffer $jobOffer): string
    {
        $userSkills = $user->skills()->pluck('label')->implode(', ');
        $userLangs = $user->languages()->withPivot('level')->get()->map(fn($l) => "{$l->label} ({$l->pivot->level})")->implode(', ');
        
        $jobSkills = $jobOffer->skills()->wherePivot('is_required', true)->pluck('label')->implode(', ');
        $jobOptionalSkills = $jobOffer->skills()->wherePivot('is_required', false)->pluck('label')->implode(', ');
        $jobLangs = $jobOffer->languages()->withPivot('level')->get()->map(fn($l) => "{$l->label} ({$l->pivot->level})")->implode(', ');

        return "
        Tu es un expert en recrutement francophone belge.
        Analyse la correspondance entre ce candidat et cette offre.

        ## Profil du candidat
        - Compétences : {$userSkills}
        - Langues : {$userLangs}
        - Profil : {$user->profile_text}

        ## Offre d'emploi
        - Titre : {$jobOffer->title}
        - Métier : {$jobOffer->metier?->label}
        - Compétences requises : {$jobSkills}
        - Compétences souhaitées : {$jobOptionalSkills}
        - Langues requises : {$jobLangs}

        ## Description
        " . strip_tags($jobOffer->description) . "

        Réponds UNIQUEMENT en JSON avec : score (0-100), points_forts (array), points_faibles (array), recommandation (string).
        ";
    }
}
