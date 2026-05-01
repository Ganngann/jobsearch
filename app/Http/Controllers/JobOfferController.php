<?php

namespace App\Http\Controllers;

use App\Models\JobOffer;
use App\Models\UserMatch;
use App\Services\MatchingService;
use App\Services\JobOfferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobOfferController extends Controller
{
    protected $matchingService;
    protected $jobOfferService;
    protected $jobMatcher;

    public function __construct(
        MatchingService $matchingService, 
        JobOfferService $jobOfferService,
        \App\Services\JobMatcherService $jobMatcher
    ) {
        $this->matchingService = $matchingService;
        $this->jobOfferService = $jobOfferService;
        $this->jobMatcher = $jobMatcher;
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
     * Rafraîchit les données de l'offre depuis l'API Forem.
     */
    public function refresh(JobOffer $jobOffer)
    {
        $this->jobOfferService->syncFullDetails($jobOffer);
        return back()->with('status', 'Données de l\'offre rafraîchies !');
    }

    /**
     * Lance le matching (IA) à la demande pour une offre spécifique.
     */
    public function match(JobOffer $jobOffer)
    {
        $user = Auth::user();

        // Si l'offre n'a pas encore ses détails complets, on les récupère d'abord
        if (!$jobOffer->is_detailed) {
            $this->jobOfferService->syncFullDetails($jobOffer);
            $jobOffer->load(['employer', 'metier', 'skills', 'languages', 'permits', 'sectors']);
        }

        // On force l'analyse IA
        $this->matchingService->match($user, $jobOffer, true);

        return back()->with('status', 'Analyse IA terminée avec succès !');
    }

    /**
     * Affiche le détail d'une offre et son analyse de matching.
     */
    public function show($id)
    {
        $user = Auth::user();

        // On cherche par ID interne ou ID Forem
        $jobOffer = JobOffer::where('id', $id)
            ->orWhere('forem_id', $id)
            ->first();

        if (!$jobOffer) {
            // On s'assure d'avoir un employeur par défaut pour la contrainte SQL
            $placeholderEmployer = \App\Models\Employer::firstOrCreate(
                ['label' => 'Forem (Importation...)']
            );

            // Création d'un squelette temporaire
            $jobOffer = JobOffer::create([
                'forem_id' => $id,
                'forem_ref' => 'F-' . $id,
                'title' => 'Importation en cours...',
                'employer_id' => $placeholderEmployer->id,
                'description' => 'Chargement des données depuis le Forem...',
                'contract_type' => '...',
                'working_regime' => '...',
            ]);
            
            $this->jobOfferService->syncFullDetails($jobOffer);
        }

        // LAZY LOADING : Si l'offre n'a pas ses détails complets
        if (!$jobOffer->is_detailed) {
            $this->jobOfferService->syncFullDetails($jobOffer);
            $jobOffer->refresh();
        }

        $jobOffer->load(['employer', 'metier', 'skills', 'languages', 'permits', 'requiredExperiences', 'studies']);

        $match = UserMatch::where('user_id', $user->id)
            ->where('job_offer_id', $jobOffer->id)
            ->first();

        // Si aucun match n'existe (même pas le pre-score), on le crée (statique uniquement)
        if (!$match) {
            $match = $this->matchingService->match($user, $jobOffer, false);
        }

        $hardScore = $this->jobMatcher->calculateHardScore($user, $jobOffer);

        return view('job-offers.show', compact('jobOffer', 'match', 'hardScore'));
    }
}
