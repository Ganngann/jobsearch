<?php

namespace App\Http\Controllers;

use App\Models\JobOffer;
use App\Models\User;
use App\Services\VectorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VectorController extends Controller
{
    protected VectorService $vectorService;

    public function __construct(VectorService $vectorService)
    {
        $this->vectorService = $vectorService;
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
                $score = $this->vectorService->cosineSimilarity($user->vector_embedding, $jobOffer->vector_embedding);
                $user->matches()->updateOrCreate(
                    ['job_offer_id' => $jobOffer->id],
                    ['vector_score' => $score]
                );
            }

            $msg = 'Vecteur et score mis à jour avec succès.';
            return request()->expectsJson() 
                ? response()->json([
                    'message' => $msg,
                    'score' => isset($score) ? round($score * 100) : null
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

        // On récupère toutes les offres actives détaillées
        $jobs = JobOffer::where('status', 'active')
            ->where('is_detailed', true)
            ->whereNotNull('vector_embedding')
            ->get();

        $count = 0;
        foreach ($jobs as $job) {
            $score = $this->vectorService->cosineSimilarity($user->vector_embedding, $job->vector_embedding);
            
            // On met à jour le UserMatch existant ou on en crée un
            $user->matches()->updateOrCreate(
                ['job_offer_id' => $job->id],
                ['vector_score' => $score]
            );
            $count++;
        }

        $msg = "Similitude calculée pour {$count} offres.";
        return request()->expectsJson() 
            ? response()->json(['message' => $msg, 'count' => $count]) 
            : back()->with('status', $msg);
    }
}
