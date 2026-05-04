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
    public function match(User $user, JobOffer $jobOffer, bool $forceAi = false, bool $triggerAi = true): UserMatch
    {
        // 1. Layer 1 — Pré-score (Statique)
        $preMatchData = $this->calculatePreScore($user, $jobOffer);
        $preScore = $preMatchData['score'];

        $match = UserMatch::updateOrCreate(
            ['user_id' => $user->id, 'job_offer_id' => $jobOffer->id],
            [
                'pre_score' => $preScore,
                'pre_score_details' => $preMatchData['details']
            ]
        );

        // 2. Layer 2 — Analyse IA
        // On lance l'IA si :
        // - On force l'analyse (demande manuelle)
        // - OU (Le pre-score est élevé >= 70 ET pas encore d'analyse faite ET trigger autorisé)
        if ($forceAi || ($triggerAi && $preScore >= 70 && !$match->analyzed_at)) {
            $this->performAiAnalysis($user, $jobOffer, $match);
        }

        return $match;
    }

    /**
     * Calcule un score rapide basé sur les compétences, langues et permis.
     * Respecte la stratégie de "Circuit Court" et les "Vetoes".
     */
    public function calculatePreScore(User $user, JobOffer $jobOffer): array
    {
        // 1. Circuit-Court : Métier (ROME)
        $blacklistedMetierIds = $user->blacklistedMetiers()->pluck('metiers.id')->toArray();
        if ($jobOffer->metier_id && in_array($jobOffer->metier_id, $blacklistedMetierIds)) {
            return 0; // Blacklisté explicitement au niveau Forem
        }

        $vetoPenalty = 0;

        // Handicap : Famille ROME Ignorée
        $blacklistedRomeCodes = $user->blacklistedReferentielMetiers()->pluck('code')->toArray();
        if ($jobOffer->metier && $jobOffer->metier->code) {
            foreach ($blacklistedRomeCodes as $romeCode) {
                if (str_starts_with($jobOffer->metier->code, $romeCode)) {
                    $vetoPenalty += 40; // Gros handicap mais pas éliminatoire
                    break;
                }
            }
        }

        // On ne court-circuite plus les métiers non-favoris à 0.
        // On calcule le score normalement pour tout le monde.


        // 2. Permis Obligatoires
        $userPermitIds = $user->permits()->pluck('permits.id')->toArray();
        $requiredPermits = $jobOffer->permits()->wherePivot('is_required', true)->get();
        foreach ($requiredPermits as $permit) {
            if (!in_array($permit->id, $userPermitIds)) {
                $vetoPenalty += 30; // Pénalité pour permis manquant
            }
        }

        // 3. Veto : Compétences Proscrites
        $blacklistedSkillIds = $user->blacklistedSkills()->pluck('skills.id')->toArray();
        $requiredSkills = $jobOffer->skills()->wherePivot('is_required', true)->get();
        foreach ($requiredSkills as $skill) {
            if (in_array($skill->id, $blacklistedSkillIds)) {
                $vetoPenalty += 50; // Pénalité pour compétence proscrite
            }
        }

        $score = 0;

        // 1. Métier Favori (20%)
        $userMetierIds = $user->preferredMetiers()->pluck('metiers.id')->toArray();
        $userRomeCodes = $user->preferredReferentielMetiers()->pluck('code')->toArray();
        
        $isFavorite = false;
        if ($jobOffer->metier_id && in_array($jobOffer->metier_id, $userMetierIds)) {
            $isFavorite = true;
        } elseif ($jobOffer->metier && $jobOffer->metier->code) {
            foreach ($userRomeCodes as $romeCode) {
                if (str_starts_with($jobOffer->metier->code, $romeCode)) {
                    $isFavorite = true;
                    break;
                }
            }
        }

        if ($isFavorite) {
            $score += 20;
        }

        // 4. Compétences (40% max)
        $allJobSkills = $jobOffer->skills;
        if ($allJobSkills->count() > 0) {
            $userSkillIds = $user->skills()->pluck('skills.id')->toArray();
            $matchedSkills = $allJobSkills->whereIn('id', $userSkillIds);
            
            $baseSkillScore = ($matchedSkills->count() / $allJobSkills->count()) * 40;
            
            // Pénalité si des compétences REQUISES manquent (non proscrites, juste absentes)
            $missingRequired = $requiredSkills->whereNotIn('id', $userSkillIds);
            if ($missingRequired->count() > 0) {
                $baseSkillScore *= 0.7; // Réduction de 30%
            }
            
            $score += $baseSkillScore;
        }

        // 5. Langues (5%)
        $userLangIds = $user->languages()->pluck('languages.id')->toArray();
        $requiredLangs = $jobOffer->languages;
        if ($requiredLangs->count() > 0) {
            $matchedLangs = $requiredLangs->whereIn('id', $userLangIds)->count();
            $score += ($matchedLangs / $requiredLangs->count()) * 5;
        }

        // 6. Permis (5%)
        $allJobPermits = $jobOffer->permits;
        if ($allJobPermits->count() > 0) {
            $matchedPermits = $allJobPermits->whereIn('id', $userPermitIds)->count();
            $score += ($matchedPermits / $allJobPermits->count()) * 5;
        }

        // 7. Localisation (30%) - Simulation simple pour l'instant
        if ($user->zip_code) {
            if (str_contains($jobOffer->location ?? '', $user->zip_code)) {
                $score += 30;
            } else {
                $score += 15; // À proximité ou à vérifier
            }
        }

        $finalScore = max(0, $score - $vetoPenalty);
        
        // Calcul des scores par catégorie avec gestion des "non-requis"
        $catScores = [
            'metier' => $isFavorite ? 20 : 0,
            'skills' => isset($baseSkillScore) ? round($baseSkillScore) : 0,
            'languages' => ($requiredLangs->count() > 0) ? round(($matchedLangs / $requiredLangs->count()) * 5) : 5,
            'permits' => ($allJobPermits->count() > 0) ? round(($matchedPermits / $allJobPermits->count()) * 5) : 5,
            'location' => $user->zip_code ? (str_contains($jobOffer->location ?? '', $user->zip_code) ? 30 : 15) : 0,
        ];

        // On recalcule le score final basé sur ces ajustements
        $adjustedScore = array_sum($catScores) - $vetoPenalty;

        return [
            'score' => (int) max(0, min(100, $adjustedScore)),
            'details' => [
                'categories' => [
                    'metier' => [
                        'score' => $catScores['metier'],
                        'max' => 20,
                        'label' => 'Métier (ROME)',
                        'is_missing' => !$isFavorite
                    ],
                    'skills' => [
                        'score' => $catScores['skills'],
                        'max' => 40,
                        'label' => 'Compétences',
                        'missing' => isset($missingRequired) ? $missingRequired->pluck('label')->toArray() : []
                    ],
                    'languages' => [
                        'score' => $catScores['languages'],
                        'max' => 5,
                        'label' => 'Langues',
                        'is_not_required' => $requiredLangs->count() === 0,
                        'missing' => $requiredLangs->whereNotIn('id', $userLangIds)->pluck('label')->toArray()
                    ],
                    'permits' => [
                        'score' => $catScores['permits'],
                        'max' => 5,
                        'label' => 'Permis',
                        'is_not_required' => $allJobPermits->count() === 0,
                        'missing' => $allJobPermits->whereNotIn('id', $userPermitIds)->pluck('label')->toArray()
                    ],
                    'location' => [
                        'score' => $catScores['location'],
                        'max' => 30,
                        'label' => 'Localisation',
                        'is_missing' => $user->zip_code && !str_contains($jobOffer->location ?? '', $user->zip_code)
                    ],
                ],
                'vetoes' => $vetoPenalty,
                'is_blacklisted' => ($jobOffer->metier_id && in_array($jobOffer->metier_id, $blacklistedMetierIds)),
            ]
        ];
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
                'ai_analysis_narrative' => $result['analyse_narrative'] ?? null,
                'ai_recommendation' => $result['recommandation'] ?? null,
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
        
        // Récupération de tous les récits
        $userFacts = $user->facts()
            ->pluck('content')
            ->map(fn($f) => "- " . $f)
            ->implode("\n");

        $jobSkills = $jobOffer->skills()->wherePivot('is_required', true)->pluck('label')->implode(', ');
        $jobOptionalSkills = $jobOffer->skills()->wherePivot('is_required', false)->pluck('label')->implode(', ');
        $jobLangs = $jobOffer->languages()->withPivot('level')->get()->map(fn($l) => "{$l->label} ({$l->pivot->level})")->implode(', ');

        return "
        Tu es un expert en recrutement francophone belge, spécialisé dans l'approche narrative et humaine (Dimension Humaine).
        Ta mission est d'analyser la correspondance entre un candidat et une offre d'emploi en allant au-delà des simples mots-clés techniques.

        ## 1. PROFIL DU CANDIDAT
        - Titre/Headline : {$user->headline}
        - Bio/Résumé : {$user->profile_text}
        - Aspirations : {$user->aspirations}
        - Compétences déclarées : {$userSkills}
        - Langues : {$userLangs}

        ## 2. RÉCITS & EXPÉRIENCES CONCRÈTES (La preuve par le fait)
        Voici les éléments narratifs validés par le candidat qui prouvent ses compétences et sa résilience :
        {$userFacts}

        ## 3. L'OFFRE D'EMPLOI
        - Titre : {$jobOffer->title}
        - Métier : {$jobOffer->metier?->label}
        - Compétences requises : {$jobSkills}
        - Compétences souhaitées : {$jobOptionalSkills}
        - Langues requises : {$jobLangs}

        ## 4. DESCRIPTION COMPLÈTE DE L'OFFRE
        " . strip_tags($jobOffer->description) . "

        ## TA MISSION
        1. Analyse comment les récits concrets du candidat répondent aux besoins du poste.
        2. Identifie les \"soft skills\" invisibles mais présents dans les récits (résilience, adaptabilité, etc.).
        3. Évalue si les aspirations du candidat sont en phase avec le poste.
        4. Calcule un score global (0-100).

        CONSIGNE DE STYLE : Sois EXTRÊMEMENT CONCIS. L'analyse narrative doit faire 3 lignes maximum, en allant droit au but. 

        Réponds UNIQUEMENT en JSON avec cette structure : 
        {
            \"score\": (int), 
            \"points_forts\": [string], 
            \"points_faibles\": [string], 
            \"analyse_narrative\": \"(Analyse ultra-concise, max 3 phrases)\",
            \"recommandation\": \"(Un seul conseil court)\"
        }
        ";
    }

    /**
     * Déclenche un calcul massif de matching pour un utilisateur (Cold Start).
     */
    public function triggerMassMatch(User $user): void
    {
        JobOffer::where('status', 'active')
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>=', now());
            })
            ->chunk(100, function($offers) use ($user) {
                foreach ($offers as $offer) {
                    // JAMAIS d'IA automatique lors d'un matching de masse
                    $this->match($user, $offer, false, false);
                }
            });
    }

    /**
     * Recalcule les scores pour toutes les offres d'un code ROME spécifique.
     */
    public function triggerRomeMatch(User $user, string $romeCode): void
    {
        JobOffer::whereHas('metier', function($q) use ($romeCode) {
                $q->where('code', 'LIKE', $romeCode . '%');
            })
            ->where('status', 'active')
            ->chunk(100, function($offers) use ($user) {
                foreach ($offers as $offer) {
                    $this->match($user, $offer, false, false);
                }
            });
    }

    /**
     * Recalcule les scores pour toutes les offres d'un métier spécifique.
     */
    public function triggerMetierMatch(User $user, int $metierId): void
    {
        JobOffer::where('metier_id', $metierId)
            ->where('status', 'active')
            ->chunk(100, function($offers) use ($user) {
                foreach ($offers as $offer) {
                    $this->match($user, $offer, false, false);
                }
            });
    }

    /**
     * Recalcule les scores uniquement pour les offres contenant une compétence spécifique.
     */
    public function triggerSkillMatch(User $user, \App\Models\Skill $skill): void
    {
        JobOffer::whereHas('skills', function($q) use ($skill) {
                $q->where('skills.id', $skill->id);
            })
            ->where('status', 'active')
            ->chunk(100, function($offers) use ($user) {
                foreach ($offers as $offer) {
                    $this->match($user, $offer, false, false);
                }
            });
    }
}
