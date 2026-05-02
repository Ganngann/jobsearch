<?php

namespace App\Http\Controllers;

use App\Models\JobOffer;
use App\Models\UserMatch;
use App\Services\JobMatcherService;
use App\Services\JobOfferService;
use App\Services\MatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JobOfferController extends Controller
{
    protected $jobOfferService;
    protected $matchingService;
    protected $jobMatcherService;

    public function __construct(
        JobOfferService $jobOfferService, 
        MatchingService $matchingService,
        JobMatcherService $jobMatcherService
    ) {
        $this->jobOfferService = $jobOfferService;
        $this->matchingService = $matchingService;
        $this->jobMatcherService = $jobMatcherService;
    }

    /**
     * Affiche le dashboard avec filtrage avancé et exploration.
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $query = JobOffer::query();

        // 1. Filtrage par Métier (Favori ou Exploration)
        if ($request->filled('metier_id')) {
            $query->where('metier_id', $request->metier_id);
        }

        // 2. Filtrage par Employeur
        if ($request->filled('employer_id')) {
            $query->where('employer_id', $request->employer_id);
        }

        // 3. Filtrage par Score (Data Match)
        if ($request->filled('min_score')) {
            $query->whereHas('matches', function($q) use ($user, $request) {
                $q->where('user_id', $user->id)
                  ->where('pre_score', '>=', $request->min_score);
            });
        }

        // 4. Tri
        $sort = $request->get('sort', 'score_desc');
        switch ($sort) {
            case 'recent':
                $query->orderBy('published_at', 'desc');
                break;
            case 'score_desc':
            default:
                // Pour trier par score, on doit faire une jointure
                $query->leftJoin('user_matches', function($join) use ($user) {
                    $join->on('job_offers.id', '=', 'user_matches.job_offer_id')
                         ->where('user_matches.user_id', '=', $user->id);
                })
                ->select('job_offers.*')
                ->orderByRaw('COALESCE(user_matches.final_score, user_matches.pre_score) DESC NULLS LAST');
                break;
        }

        $jobOffers = $query->with(['employer', 'metier', 'userMatch'])->paginate(20);

        if ($request->ajax() || $request->has('partial')) {
            return view('job-offers.partials.list-items', compact('jobOffers'))->render();
        }

        // Données pour les filtres de la sidebar
        $topMetiers = \App\Models\Metier::whereHas('jobOffers')
            ->withCount('jobOffers')
            ->orderBy('job_offers_count', 'desc')
            ->limit(25)
            ->get();

        $topEmployers = \App\Models\Employer::whereHas('jobOffers')
            ->withCount('jobOffers')
            ->orderBy('job_offers_count', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard', compact('jobOffers', 'user', 'topMetiers', 'topEmployers'));
    }

    /**
     * Retourne le HTML partiel pour la prévisualisation d'une offre.
     */
    public function preview(JobOffer $jobOffer)
    {
        $user = Auth::user();
        $match = $jobOffer->matches()->where('user_id', $user->id)->first();
        
        // Si pas de match, on le crée à la volée (Data Match)
        if (!$match) {
            $match = app(\App\Services\MatchingService::class)->match($user, $jobOffer, false, false);
        }

        return view('job-offers.partials.preview', compact('jobOffer', 'match', 'user'));
    }

    /**
     * Force le matching IA pour une offre spécifique.
     */
    public function match(Request $request, JobOffer $jobOffer)
    {
        $user = Auth::user();

        // On s'assure d'avoir les détails complets (skills, etc.)
        if (!$jobOffer->is_detailed) {
            $this->jobOfferService->syncFullDetails($jobOffer);
            $jobOffer->load(['employer', 'metier', 'skills', 'languages', 'permits', 'sectors']);
        }

        // On force l'analyse IA
        $this->matchingService->match($user, $jobOffer, true);

        return back()->with('status', 'Analyse IA terminée avec succès !');
    }

    /**
     * Affiche le détail complet d'une offre.
     */
    public function show($id)
    {
        $user = Auth::user();
        $jobOffer = JobOffer::where('id', $id)->orWhere('forem_id', $id)->firstOrFail();

        if (!$jobOffer->is_detailed) {
            $this->jobOfferService->syncFullDetails($jobOffer);
        }

        $match = $jobOffer->matches()->where('user_id', $user->id)->first();
        if (!$match) {
            $match = $this->matchingService->match($user, $jobOffer, false, false);
        }

        $hardScore = $this->jobMatcherService->calculateHardScore($user, $jobOffer);

        return view('job-offers.show', compact('jobOffer', 'match', 'user', 'hardScore'));
    }

    /**
     * Synchronise les offres depuis le Forem.
     */
    public function sync()
    {
        // Simple redirection vers la commande ou appel direct au service
        // Pour l'instant, on laisse l'importation massive à la console
        return back()->with('status', 'Utilisez la commande artisan app:reset-and-import pour synchroniser 1000 offres.');
    }

    /**
     * Sert le logo d'un employeur directement pour économiser de la mémoire.
     */
    public function logo(\App\Models\Employer $employer)
    {
        if (!$employer->logo_base64) {
            return response()->noContent(404);
        }

        $data = base64_decode($employer->logo_base64);
        
        return response($data)
            ->header('Content-Type', $employer->logo_mime_type)
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
