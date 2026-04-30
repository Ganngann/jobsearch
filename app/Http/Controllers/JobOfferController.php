<?php

namespace App\Http\Controllers;

use App\Models\JobOffer;
use App\Models\UserMatch;
use App\Services\MatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobOfferController extends Controller
{
    protected $matchingService;

    public function __construct(MatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    /**
     * Affiche le tableau de bord avec TOUTES les offres.
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Récupérer TOUTES les offres, avec le match de l'utilisateur s'il existe
        $jobOffers = JobOffer::with(['employer', 'metier', 'matches' => function($query) use ($user) {
            $query->where('user_id', $user->id);
        }])
        ->orderByDesc('published_at')
        ->paginate(12);

        return view('dashboard', compact('jobOffers'));
    }

    /**
     * Lance le matching (IA) à la demande pour une offre spécifique.
     */
    public function match(JobOffer $jobOffer)
    {
        $user = Auth::user();

        // On force l'analyse IA
        $this->matchingService->match($user, $jobOffer, true);

        return back()->with('status', 'Analyse IA terminée avec succès !');
    }

    /**
     * Affiche le détail d'une offre et son analyse de matching.
     */
    public function show(JobOffer $jobOffer)
    {
        $user = Auth::user();

        $match = UserMatch::where('user_id', $user->id)
            ->where('job_offer_id', $jobOffer->id)
            ->first();

        // Si aucun match n'existe (même pas le pre-score), on le crée (statique uniquement)
        if (!$match) {
            $match = $this->matchingService->match($user, $jobOffer, false);
        }

        return view('job-offers.show', compact('jobOffer', 'match'));
    }
}
