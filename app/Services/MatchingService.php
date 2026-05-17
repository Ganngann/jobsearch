<?php

namespace App\Services;

use App\Models\JobOffer;
use App\Models\User;
use App\Models\UserMatch;
use App\Models\ZipCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class MatchingService
{
    protected $gemini;
    protected $vectorService;

    public function __construct(GeminiService $gemini, VectorService $vectorService)
    {
        $this->gemini = $gemini;
        $this->vectorService = $vectorService;
    }

    /**
     * Calcule le score de correspondance complet (Pre-score + IA si nécessaire).
     */
    public function match(User $user, JobOffer $jobOffer, bool $forceAi = false, bool $triggerAi = true, array $context = null): ?UserMatch
    {
        // On ne calcule pas de match pour une offre dont le détail n'a pas encore été récupéré.
        // Le matching sans skills/description complète n'est pas pertinent.
        if (!$jobOffer->is_detailed) {
            return null;
        }

        // 1. Layer 1 — Score Sémantique (Fond)
        $semanticScore = 0;
        if ($user->vector_embedding && $jobOffer->vector_embedding) {
            $semanticScore = $this->vectorService->calculateSemanticScore($user->vector_embedding, $jobOffer->vector_embedding);
        }

        // 2. Layer 2 — Pré-score / Attractivité (Forme)
        $preMatchData = $this->calculatePreScore($user, $jobOffer, $context);
        $attractivityScore = $preMatchData['score'];

        // 3. Score Final (Hiérarchie : Expertise IA > Produit Sémantique)
        $match = UserMatch::firstOrNew(['user_id' => $user->id, 'job_offer_id' => $jobOffer->id]);
        
        // Si l'IA a déjà donné son expertise, elle devient le score maître.
        // Sinon, on calcule le produit Sémantique * Attractivité.
        $finalScore = ($match->ai_score) 
            ? $match->ai_score 
            : round($semanticScore * ($attractivityScore / 100));

        $match->fill([
            'vector_score' => $semanticScore,
            'pre_score' => $attractivityScore,
            'pre_score_details' => $preMatchData['details'],
            'final_score' => $finalScore,
        ]);
        
        if (!$match->exists || !$match->ai_status) {
            $match->ai_status = 'pending';
        }
        $match->save();

        // 2. Layer 2 — Analyse IA
        // On lance l'IA si :
        // - On force l'analyse (demande manuelle)
        if ($forceAi) {
            $this->performAiAnalysis($user, $jobOffer, $match, $preMatchData['details']['distance'] ?? null);
        }

        return $match;
    }

    /**
     * Calcule le score d'attractivité (Pre-score) basé sur la soustraction.
     * Philosophie : On part de 100 et on retire les points de friction.
     */
    public function calculatePreScore(User $user, JobOffer $jobOffer, array $context = null): array
    {
        $config = config('matching');
        $attractivity = $config['base_score'];
        $details = ['penalties' => [], 'bonuses' => []];

        // --- 0. DÉTECTION DONNÉES PAUVRES ---
        // On marque l'offre si elle n'a pas de données techniques, mais on continue pour la localisation.
        $isPoorData = $jobOffer->skills->isEmpty() && $jobOffer->languages->isEmpty() && $jobOffer->permits->isEmpty();

        // --- 1. HANDICAPS (SOUSTRACTION) ---

        // A. Métier Refusé
        $userMetiers = $context['preferred_metiers'] ?? $user->preferredMetiers;
        $jobMetierId = $jobOffer->metier_id;
        $isRefusedMetier = $userMetiers->where('id', $jobMetierId)->where('pivot.status', 'refused')->isNotEmpty();
        
        if ($isRefusedMetier) {
            $penalty = $config['handicaps']['refused_metier'];
            $attractivity -= $penalty;
            $details['penalties'][] = ['label' => 'Métier refusé', 'value' => -$penalty, 'type' => 'metier_refused'];
        }

        // B. Compétences Refusées (JIT)
        $refusedSkillIds = $context['refused_skill_ids'] ?? DB::table('user_skill')
            ->where('user_id', $user->id)
            ->where('status', 'refused')
            ->pluck('skill_id')
            ->toArray();
            
        $refusedSkillsInJob = $jobOffer->skills->whereIn('id', $refusedSkillIds);
        if ($refusedSkillsInJob->isNotEmpty()) {
            $penalty = $refusedSkillsInJob->count() * $config['handicaps']['refused_skill'];
            $attractivity -= $penalty;
            $details['penalties'][] = [
                'label' => 'Compétences refusées (' . $refusedSkillsInJob->count() . ')', 
                'value' => -$penalty, 
                'type' => 'skill_refused',
                'items' => $refusedSkillsInJob->pluck('label')->toArray()
            ];
        }

        // C. Permis Requis Manquant
        $userPermitIds = $context['permit_ids'] ?? $user->permits()->pluck('permits.id')->toArray();
        $missingRequiredPermits = $jobOffer->permits->where('pivot.is_required', true)->whereNotIn('id', $userPermitIds);
        
        if ($missingRequiredPermits->isNotEmpty()) {
            $penalty = $config['handicaps']['missing_permit'];
            $attractivity -= $penalty;
            $details['penalties'][] = [
                'label' => 'Permis requis manquant', 
                'value' => -$penalty, 
                'type' => 'permit_missing',
                'items' => $missingRequiredPermits->pluck('label')->toArray()
            ];
        }

        // D. Langue Requise Manquante
        $userLangIds = $context['language_ids'] ?? $user->languages()->pluck('languages.id')->toArray();
        $missingRequiredLangs = $jobOffer->languages->where('pivot.is_required', true)->whereNotIn('id', $userLangIds);
        
        if ($missingRequiredLangs->isNotEmpty()) {
            $penalty = $config['handicaps']['missing_language'];
            $attractivity -= $penalty;
            $details['penalties'][] = [
                'label' => 'Langue requise manquante', 
                'value' => -$penalty, 
                'type' => 'language_missing',
                'items' => $missingRequiredLangs->pluck('label')->toArray()
            ];
        }

        // E. Préférence de Contrat
        if (!empty($user->contract_preferences)) {
            $jobContract = $jobOffer->contract_type;
            if (!in_array($jobContract, $user->contract_preferences)) {
                $penalty = $config['handicaps']['contract_mismatch'];
                $attractivity -= $penalty;
                $details['penalties'][] = [
                    'label' => 'Contrat non souhaité (' . $jobContract . ')',
                    'value' => -$penalty,
                    'type' => 'contract_mismatch',
                    'meta' => ['current' => $jobContract, 'preferred' => $user->contract_preferences]
                ];
            }
        }

        // --- 2. LOCALISATION (FRICTION) ---
        $distance = null;
        if ($user->zip_code) {
            $userZip = $context['user_zip'] ?? ZipCode::where('zip_code', $user->zip_code)->first();
            $jobCoords = $this->getJobCoords($jobOffer);
            
            if ($userZip && $jobCoords) {
                $distance = $this->calculateDistance($userZip->latitude, $userZip->longitude, $jobCoords['lat'], $jobCoords['lon']);
                $radius = $user->radius ?? $config['location']['default_radius'];
                $freeRadius = $config['location']['free_radius'] ?? 5;
                
                // Distance effective après immunité des premiers km
                $effDistance = max(0, $distance - $freeRadius);
                
                // Formule de friction calibrée :
                // 1. Pénalité nulle sous le free_radius.
                // 2. Pénalité de exactement 1.0 à la limite du rayon (radius).
                // 3. Tend vers max_penalty pour les distances extrêmes.
                if ($effDistance > 0 && $radius > $freeRadius) {
                    $maxP = (float) $config['location']['max_penalty'];
                    $pAtR = (float) ($config['location']['penalty_at_radius'] ?? 1);
                    
                    // On calcule la constante K pour que la pénalité soit de $pAtR à la limite du $radius
                    $k = (($maxP / $pAtR) - 1) * ($radius - $freeRadius);
                    $penalty = $maxP * ($effDistance / ($effDistance + $k));
                } else {
                    $penalty = 0;
                }
                
                // Bonus Télétravail (Détection sommaire)
                $isTelework = preg_match('/télétravail|remote|home-office/i', $jobOffer->description);
                if ($isTelework) {
                    $penalty /= 2;
                }

                if ($penalty > 0.5) { // On ne pénalise pas pour moins d'un demi-point
                    $attractivity -= $penalty;
                    $details['penalties'][] = [
                        'label' => 'Distance (' . round($distance, 1) . 'km)', 
                        'value' => -round($penalty, 1), 
                        'type' => 'distance',
                        'meta' => ['distance' => $distance, 'telework' => $isTelework]
                    ];
                }
            }
        }

        // --- 3. VÉTUSTÉ ---
        $daysOld = $jobOffer->published_at ? $jobOffer->published_at->diffInDays(now()) : 0;
        if ($daysOld > $config['freshness']['start_after_days']) {
            $penalty = min($config['freshness']['max_malus'], ($daysOld - $config['freshness']['start_after_days']) * $config['freshness']['malus_per_day']);
            $attractivity -= $penalty;
            $details['penalties'][] = ['label' => 'Ancienneté de l\'offre', 'value' => -round($penalty, 1), 'type' => 'freshness'];
        }

        // --- 4. BONUSES (AFFINITÉ) ---

        // A. Métier Favori
        $isFavoriteMetier = $userMetiers->where('id', $jobMetierId)->where('pivot.status', 'favorite')->isNotEmpty();
        if ($isFavoriteMetier) {
            $bonus = $config['bonuses']['favorite_metier'];
            $attractivity += $bonus;
            $details['bonuses'][] = ['label' => 'Métier favori', 'value' => $bonus, 'type' => 'metier_favorite'];
        }

        // B. Compétences Validées (Active)
        $userSkillIds = $context['skill_ids'] ?? $user->validatedSkills()->pluck('skills.id')->toArray();
        $matchedSkills = $jobOffer->skills->whereIn('id', $userSkillIds);
        if ($matchedSkills->isNotEmpty()) {
            $bonus = $matchedSkills->count() * $config['bonuses']['active_skill'];
            $attractivity += $bonus;
            $details['bonuses'][] = [
                'label' => 'Compétences maîtrisées (' . $matchedSkills->count() . ')', 
                'value' => $bonus, 
                'type' => 'skill_matched',
                'items' => $matchedSkills->pluck('label')->toArray()
            ];
        }

        // Clamp final (0-100)
        $finalAttractivity = (int) max(0, min(100, $attractivity));

        return [
            'score' => $finalAttractivity,
            'details' => [
                'base' => $config['base_score'],
                'penalties' => $details['penalties'],
                'bonuses' => $details['bonuses'],
                'distance' => $distance ? round($distance, 1) : null,
                'is_telework' => $isTelework ?? false,
                'is_poor_data' => $isPoorData,
            ]
        ];
    }



    /**
     * Effectue l'analyse sémantique avec Gemini.
     */
    public function performAiAnalysis(User $user, JobOffer $jobOffer, UserMatch $match, float $distance = null): bool
    {
        $prompt = $this->buildPrompt($user, $jobOffer, $distance);
        Log::info("Sending request to Gemini for JobOffer #{$jobOffer->forem_id}");
        $result = $this->gemini->forUser($user)->analyzeMatch($prompt);

        if ($result) {
            $match->update([
                'ai_score' => $result['score'] ?? null,
                'ai_at_pre_score' => $match->pre_score, // On mémorise le pre_score lors de l'analyse
                'final_score' => $result['score'] ?? $match->pre_score,
                'strengths' => $result['points_forts'] ?? [],
                'weaknesses' => $result['points_faibles'] ?? [],
                'ai_analysis_narrative' => $result['analyse_narrative'] ?? null,
                'ai_recommendation' => $result['recommandation'] ?? null,
                'ai_raw_response' => $result,
                'analyzed_at' => now(),
            ]);
            
            $this->gemini->log('match', $user->id);

            Log::info("Gemini analysis successful for JobOffer #{$jobOffer->forem_id}. Score: {$result['score']}");
            return true;
        }

        Log::error("Gemini analysis returned null for JobOffer #{$jobOffer->forem_id}");

        return false;
    }

    /**
     * Construit le prompt pour l'IA.
     */
    protected function buildPrompt(User $user, JobOffer $jobOffer, float $distance = null): string
    {
        $allUserSkills = $user->validatedSkills;
        $userHardSkills = $allUserSkills->where('type', 'hard')->pluck('label')->implode(', ');
        $userSoftSkills = $allUserSkills->where('type', 'soft')->pluck('label')->implode(', ');

        $userLangs = $user->languages->map(fn($l) => "{$l->label} ({$l->pivot->level})")->implode(', ');
        
        // Récupération de tous les récits
        $userFacts = $user->facts()
            ->pluck('content')
            ->map(fn($f) => "- " . $f)
            ->implode("\n");

        $jobSkills = $jobOffer->skills->where('pivot.is_required', true)->pluck('label')->implode(', ');
        $jobOptionalSkills = $jobOffer->skills->where('pivot.is_required', false)->pluck('label')->implode(', ');
        $jobLangs = $jobOffer->languages->map(fn($l) => "{$l->label} ({$l->pivot->level})")->implode(', ');

        return "
        Tu es un expert en recrutement francophone belge, spécialisé dans l'approche narrative et humaine (Dimension Humaine).
        Ta mission est d'analyser la correspondance entre un candidat et une offre d'emploi en allant au-delà des simples mots-clés techniques.

        ## 1. PROFIL DU CANDIDAT
        - Titre/Headline : {$user->headline}
        - Bio/Résumé : {$user->profile_text}
        - Aspirations : {$user->aspirations}
        - Compétences Techniques : {$userHardSkills}
        - Soft Skills : {$userSoftSkills}
        - Langues : {$userLangs}
        - Mobilité : Rayon maximum de " . ($user->radius ?? 30) . " km autour de son domicile.
        - Préférences de contrat : " . (empty($user->contract_preferences) ? "Aucune préférence particulière" : implode(', ', $user->contract_preferences)) . "

        ## 2. RÉCITS & EXPÉRIENCES CONCRÈTES (La preuve par le fait)
        Voici les éléments narratifs validés par le candidat qui prouvent ses compétences et sa résilience :
        {$userFacts}

        ## 3. L'OFFRE D'EMPLOI
        - Titre : {$jobOffer->title}
        - Métier : {$jobOffer->metier?->label}
        - Type de contrat : {$jobOffer->contract_type}
        - Compétences requises : {$jobSkills}
        - Compétences souhaitées : {$jobOptionalSkills}
        - Langues requises : {$jobLangs}
        - Localisation de l'offre : {$jobOffer->location}" . ($distance ? " (Distance réelle : {$distance} km du domicile)" : "") . "

        ## 4. DESCRIPTION COMPLÈTE DE L'OFFRE
        " . strip_tags($jobOffer->description) . "

        ## TA MISSION
        1. Analyse comment les récits concrets du candidat répondent aux besoins du poste.
        2. Identifie les \"soft skills\" invisibles mais présents dans les récits (résilience, adaptabilité, etc.).
        3. Évalue si les aspirations du candidat sont en phase avec le poste.
        4. Analyse la faisabilité géographique : si la distance réelle dépasse le rayon souhaité, mentionne-le comme un point d'attention ou de vigilance, mais pondère-le en fonction de la distance.
        5. Vérifie si le type de contrat de l'offre correspond aux préférences du candidat. Si ce n'est pas le cas, cela doit impacter négativement le score et être mentionné comme point faible.
        6. Calcule un score global (0-100).

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
        \App\Jobs\MatchUserJob::dispatch($user);
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
            ->where('is_detailed', true)
            ->chunkById(100, function($offers, $index) use ($user) {
                \App\Jobs\MatchChunkJob::dispatch($user, $offers->pluck('id')->toArray())
                    ->delay(now()->addSeconds($index * 1));
            });
    }

    /**
     * Recalcule les scores pour toutes les offres d'un métier spécifique.
     */
    public function triggerMetierMatch(User $user, int $metierId): void
    {
        JobOffer::where('metier_id', $metierId)
            ->where('status', 'active')
            ->where('is_detailed', true)
            ->chunkById(100, function($offers, $index) use ($user) {
                \App\Jobs\MatchChunkJob::dispatch($user, $offers->pluck('id')->toArray())
                    ->delay(now()->addSeconds($index * 1));
            });
    }

    /**
     * Tente de trouver les coordonnées d'une offre à partir de son libellé location.
     * Utilise le cache sur le modèle JobOffer pour éviter les calculs redondants.
     */
    private function getJobCoords(JobOffer $jobOffer): ?array
    {
        // Si déjà en cache sur l'offre, on l'utilise direct
        if ($jobOffer->latitude && $jobOffer->longitude) {
            return ['lat' => $jobOffer->latitude, 'lon' => $jobOffer->longitude];
        }

        $location = $jobOffer->location;
        if (!$location) return null;

        $coords = null;

        // 1. Cherche un code postal dans le texte (4 chiffres pour la Belgique)
        if (preg_match('/(\d{4})/', $location, $matches)) {
            $zip = ZipCode::where('zip_code', $matches[1])->first();
            if ($zip) $coords = ['lat' => $zip->latitude, 'lon' => $zip->longitude];
        }

        if (!$coords) {
            // 2. Nettoyage des préfixes administratifs courants
            $cleanLocation = trim(str_ireplace(['Arrondissement de', 'Province de', 'Région de'], '', $location));
            
            // 3. Cherche par nom de ville exact (avec fallback sur le casing)
            $zip = ZipCode::where('city', $cleanLocation)->first();
            
            if (!$zip) {
                $zip = ZipCode::where('city', \Illuminate\Support\Str::title(mb_strtolower($cleanLocation)))->first();
            }

            if ($zip) $coords = ['lat' => $zip->latitude, 'lon' => $zip->longitude];

            // 4. Recherche LIKE (plus lent)
            if (!$coords && strlen($cleanLocation) > 3) {
                $zip = ZipCode::whereRaw('? LIKE "%" || city || "%"', [$cleanLocation])->first();
                if ($zip) $coords = ['lat' => $zip->latitude, 'lon' => $zip->longitude];
            }
        }

        // Si on a trouvé, on met à jour l'offre pour la prochaine fois (Silencieusement)
        if ($coords) {
            $jobOffer->updateQuietly([
                'latitude' => $coords['lat'],
                'longitude' => $coords['lon']
            ]);
        }

        return $coords;
    }

    /**
     * Formule de Haversine pour calculer la distance entre deux points GPS (en km).
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
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
            ->where('is_detailed', true)
            ->chunkById(100, function($offers, $index) use ($user) {
                \App\Jobs\MatchChunkJob::dispatch($user, $offers->pluck('id')->toArray())
                    ->delay(now()->addSeconds($index * 1));
            });
    }

    /**
     * Déclenche l'analyse IA pour les 20 meilleures offres d'un utilisateur.
     */
    public function triggerTopAiAnalysis(User $user): void
    {
        $topMatches = UserMatch::where('user_id', $user->id)
            ->whereNull('analyzed_at')
            ->where('ai_status', 'pending')
            ->orderBy('final_score', 'desc')
            ->orderBy('pre_score', 'desc')
            ->limit(20)
            ->get();

        foreach ($topMatches as $match) {
            $jobOffer = $match->jobOffer;
            if ($jobOffer && $jobOffer->is_detailed) {
                \App\Jobs\AnalyzeJobOffer::dispatch($user, $jobOffer, $match);
            }
        }
    }
}

