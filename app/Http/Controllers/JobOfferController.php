<?php

namespace App\Http\Controllers;

use App\Models\JobOffer;
use App\Models\UserMatch;
use App\Services\MatchingService;
use App\Services\JobOfferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
     * Affiche le tableau de bord avec filtres.
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $query = JobOffer::with(['employer', 'metier', 'matches' => function($q) use ($user) {
            $q->where('user_id', $user->id);
        }]);

        // Filtrage par recherche (Titre ou Employeur)
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('employer', function($sq) use ($search) {
                      $sq->where('label', 'like', "%{$search}%");
                  });
            });
        }

        // Filtrage par type de contrat
        if ($contract = $request->input('contract')) {
            $query->where('contract_type', $contract);
        }

        // Filtrage par score minimum
        if ($minScore = $request->input('min_score')) {
            $offerIds = DB::table('user_matches')
                ->where('user_id', $user->id)
                ->where(function($q) use ($minScore) {
                    $q->where('final_score', '>=', $minScore)
                      ->orWhere(function($sq) use ($minScore) {
                          $sq->whereNull('final_score')
                             ->where('pre_score', '>=', $minScore);
                      });
                })
                ->pluck('job_offer_id');

            $query->whereIn('job_offers.id', $offerIds);
        }

        // Gestion du tri
        $sortBy = $request->input('sort_by', 'date_desc');

        if ($sortBy === 'score_desc') {
            $query->leftJoin('user_matches', function($join) use ($user) {
                $join->on('job_offers.id', '=', 'user_matches.job_offer_id')
                     ->where('user_matches.user_id', '=', $user->id);
            })
            ->select('job_offers.*')
            ->orderByRaw('COALESCE(user_matches.final_score, user_matches.pre_score) DESC');
        } elseif ($sortBy === 'title_asc') {
            $query->orderBy('title', 'asc');
        } else {
            $query->orderByRaw('job_offers.published_at IS NULL, job_offers.published_at DESC');
        }

        $jobOffers = $query->paginate(12)
            ->withQueryString();

        $contractTypes = JobOffer::distinct()->pluck('contract_type')->filter()->sort();

        return view('dashboard', compact('jobOffers', 'contractTypes'));
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
