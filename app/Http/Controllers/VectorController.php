<?php

namespace App\Http\Controllers;

use App\Models\JobOffer;
use App\Models\UserMatch;
use App\Services\VectorService;
use App\Services\MatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VectorController extends Controller
{
    protected VectorService $vectorService;
    protected MatchingService $matchingService;

    public function __construct(VectorService $vectorService, MatchingService $matchingService)
    {
        $this->vectorService = $vectorService;
        $this->matchingService = $matchingService;
    }

    /**
     * Déclenche la vectorisation manuelle d'une offre d'emploi.
     */
    public function embedJob(JobOffer $jobOffer)
    {
        $success = $this->vectorService->updateJobVector($jobOffer);

        if ($success) {
            // Recalculer immédiatement la similitude pour l'utilisateur actuel
            $user = auth()->user();
            if ($user && $user->vector_embedding) {
                $score = $this->vectorService->calculateSemanticScore($user->vector_embedding, $jobOffer->vector_embedding);
                
                // On récupère le pre_score existant pour mettre à jour le final_score cohérent
                $existingMatch = $user->matches()->where('job_offer_id', $jobOffer->id)->first();
                $preScore = $existingMatch ? $existingMatch->pre_score : 100;
                
                // Si l'IA a déjà donné son expertise, c'est le score maître
                $finalScore = ($existingMatch && $existingMatch->ai_score) 
                    ? $existingMatch->ai_score 
                    : round($score * ($preScore / 100));

                $user->matches()->updateOrCreate(
                    ['job_offer_id' => $jobOffer->id],
                    [
                        'vector_score' => $score,
                        'final_score' => $finalScore
                    ]
                );
            }

            $msg = 'Vecteur et score mis à jour avec succès.';
            return request()->expectsJson() 
                ? response()->json([
                    'message' => $msg,
                    'score' => isset($score) ? round($score) : null
                ]) 
                : back()->with('status', $msg);
        }

        $msg = 'Échec de la vectorisation de l\'offre.';
        return request()->expectsJson() 
            ? response()->json(['error' => $msg], 500) 
            : back()->with('error', $msg);
    }

    /**
     * Déclenche la vectorisation manuelle du profil utilisateur.
     */
    public function embedProfile()
    {
        $user = auth()->user();
        $success = $this->vectorService->updateUserVector($user);

        if ($success) {
            $msg = 'Votre vecteur de profil a été mis à jour.';
            return request()->expectsJson() 
                ? response()->json(['message' => $msg]) 
                : back()->with('status', $msg);
        }

        $msg = 'Échec de la vectorisation de votre profil.';
        return request()->expectsJson() 
            ? response()->json(['error' => $msg], 500) 
            : back()->with('error', $msg);
    }

    /**
     * Calcule la similitude vectorielle pour toutes les offres du dashboard.
     */
    public function syncSimilarities()
    {
        $user = auth()->user();
        if (!$user->vector_embedding) {
            $msg = 'Veuillez d\'abord vectoriser votre profil.';
            return request()->expectsJson() 
                ? response()->json(['error' => $msg], 422) 
                : back()->with('error', $msg);
        }

        // Lever la limite de temps pour ce calcul massif
        set_time_limit(0);

        $count = 0;
        $upsertData = [];
        
        // Utilisation de chunkById et select pour éviter l'épuisement de la mémoire
        JobOffer::select(['id', 'vector_embedding'])
            ->where('status', 'active')
            ->where('is_detailed', true)
            ->whereNotNull('vector_embedding')
            ->chunkById(500, function ($jobs) use (&$count, &$upsertData, $user) {
                foreach ($jobs as $job) {
                    $score = $this->vectorService->calculateSemanticScore($user->vector_embedding, $job->vector_embedding);

                    $upsertData[] = [
                        'user_id' => $user->id,
                        'job_offer_id' => $job->id,
                        'vector_score' => $score
                    ];

                    $count++;

                    // On traite par paquets de 500 pour ne pas saturer la requête SQL
                    if (count($upsertData) >= 500) {
                        UserMatch::upsert($upsertData, ['user_id', 'job_offer_id'], ['vector_score']);
                        $upsertData = [];
                    }
                }
            });

        // Dernier paquet
        if (!empty($upsertData)) {
            UserMatch::upsert($upsertData, ['user_id', 'job_offer_id'], ['vector_score']);
        }

        // Mise à jour massive des final_score pour la cohérence (Priorité IA > Vecteur)
        UserMatch::where('user_id', $user->id)
            ->update([
                'final_score' => \Illuminate\Support\Facades\DB::raw('COALESCE(ai_score, ROUND(vector_score * (pre_score / 100)))')
            ]);

        $msg = "Similitude calculée pour {$count} offres.";
        return request()->expectsJson() 
            ? response()->json(['message' => $msg, 'count' => $count]) 
            : back()->with('status', $msg);
    }

    /**
     * Recalcule les similitudes pour TOUS les utilisateurs (Maintenance Admin).
     */
    public function syncGlobalSimilarities()
    {
        $lock = \Illuminate\Support\Facades\Cache::lock('sync_global_similarities', 60);

        if (!$lock->get()) {
            return back()->with('error', "Un recalcul global est déjà en cours ou a été lancé récemment. Veuillez patienter.");
        }

        // On lance le processus lourd en arrière-plan
        \App\Jobs\GlobalMatchingJob::dispatch();

        return back()->with('success', "Le recalcul global a été lancé en arrière-plan. Les scores seront mis à jour progressivement.");
    }


}
