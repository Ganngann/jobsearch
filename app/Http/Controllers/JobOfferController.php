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

        // Onboarding progress redirection logic
        if (!$user->isProfileMature()) {
            // Step 1: Narrative & Journey
            if ($user->getNarrativeProgress() < 100) {
                // If totally empty, show the choice page (welcome/onboarding)
                if ($user->facts()->count() == 0 && $user->experiences()->count() == 0) {
                    return redirect()->route('onboarding');
                }
                return redirect()->route('profile.builder');
            }

            // Step 2: Skills validation
            if ($user->getSkillsProgress() < 100) {
                return redirect()->route('profile.skills.index');
            }

            // Step 3: ROME Targeting
            if ($user->getRomeProgress() < 100) {
                return redirect()->route('discovery.index');
            }

            // Step 4: Mobility
            if ($user->getMobilityProgress() < 100) {
                return redirect()->route('profile.mobility.index');
            }
        }

        $query = JobOffer::query();

        // 1. Filtrage par Métier (Favori ou Exploration)
        if ($request->filled('metier_id')) {
            $query->where('metier_id', $request->metier_id);
        }

        // 2. Filtrage par Employeur
        if ($request->filled('employer_id')) {
            $query->where('employer_id', $request->employer_id);
        }

        // 2b. Filtrage par ROME (Niveau Famille)
        if ($request->filled('rome')) {
            $query->whereHas('metier', function($q) use ($request) {
                $q->where('code', 'LIKE', $request->rome . '%');
            });
        }
        // 2c. Recherche par mot-clé (Titre ou Employeur)
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function($sq) use ($q) {
                $sq->where('title', 'LIKE', "%{$q}%")
                   ->orWhereHas('employer', function($eq) use ($q) {
                       $eq->where('label', 'LIKE', "%{$q}%");
                   });
            });
        }

        // 3. Filtrage par Score (Data Match)
        if ($request->filled('min_score') && $request->min_score > 0) {
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
                // Jointure simple sur les scores pré-calculés
                $query->leftJoin('user_matches', function($join) use ($user) {
                    $join->on('job_offers.id', '=', 'user_matches.job_offer_id')
                         ->where('user_matches.user_id', '=', $user->id);
                })
                ->select('job_offers.*', 'user_matches.pre_score', 'user_matches.final_score')
                ->orderByRaw('user_matches.final_score DESC NULLS LAST')
                ->orderByRaw('user_matches.pre_score DESC NULLS LAST');
                break;
        }

        $jobOffers = $query->with(['employer', 'metier', 'userMatch'])->paginate(20);

        $favoriteRomeCodes = $user->preferredReferentielMetiers()->pluck('code')->toArray();

        if ($request->ajax() || $request->has('partial')) {
            return view('job-offers.partials.list-items', compact('jobOffers', 'favoriteRomeCodes'))->render();
        }

        // Données pour les filtres de la sidebar
        // Données pour les filtres de la sidebar : Triés par potentiel de match personnel
        $topMetiers = \App\Models\Metier::whereHas('jobOffers')
            ->withCount('jobOffers')
            ->orderBy('job_offers_count', 'desc')
            ->limit(100)
            ->get();

        $topEmployers = \App\Models\Employer::whereHas('jobOffers')
            ->withCount('jobOffers')
            ->orderBy('job_offers_count', 'desc')
            ->limit(50)
            ->get();

        $favoriteRomeCodes = $user->preferredReferentielMetiers()->pluck('code')->toArray();

        return view('dashboard', compact('jobOffers', 'user', 'topMetiers', 'topEmployers', 'favoriteRomeCodes'));
    }

    /**
     * Retourne le HTML partiel pour la prévisualisation d'une offre.
     */
    public function preview(JobOffer $jobOffer)
    {
        $user = Auth::user();
        // On s'assure d'avoir les détails pour pouvoir matcher
        if (!$jobOffer->is_detailed) {
            $this->jobOfferService->syncFullDetails($jobOffer);
            $jobOffer->load(['employer', 'metier', 'skills', 'languages', 'permits', 'sectors']);
        }

        $match = $jobOffer->matches()->where('user_id', $user->id)->first();
        
        // Si pas de match, on le crée à la volée (Data Match)
        if (!$match && $jobOffer->is_detailed) {
            $match = $this->matchingService->match($user, $jobOffer, false, false);
        }

        // Fallback pour éviter le crash de la vue si le matching échoue
        if (!$match) {
            $match = new \App\Models\UserMatch([
                'pre_score' => 0,
                'final_score' => 0,
                'strengths' => [],
                'weaknesses' => []
            ]);
        }

        $isParentFavorite = false;
        if ($jobOffer->metier && $jobOffer->metier->code) {
            $parentCode = substr($jobOffer->metier->code, 0, 5);
            $isParentFavorite = $user->preferredReferentielMetiers()->where('code', $parentCode)->exists();
        }

        // Vérification du statut de blacklist réel (pour éviter le délai du background job)
        $isOfferBlacklisted = false;
        if (!$isOfferBlacklisted && $jobOffer->metier_id) {
            $isOfferBlacklisted = $user->preferredMetiers()
                ->where('metier_id', $jobOffer->metier_id)
                ->wherePivot('status', 'refused')
                ->exists();

            if (!$isOfferBlacklisted && $jobOffer->metier->code) {
                $parentCode = substr($jobOffer->metier->code, 0, 5);
                $isOfferBlacklisted = $user->preferredReferentielMetiers()
                    ->where('code', $parentCode)
                    ->wherePivot('status', 'refused')
                    ->exists();
            }
        }

        return view('job-offers.partials.preview', compact('jobOffer', 'match', 'user', 'isParentFavorite', 'isOfferBlacklisted'));
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

        // Récupérer ou créer le match de base
        $match = $jobOffer->matches()->firstOrCreate(['user_id' => $user->id]);
        
        if ($match->ai_status === 'processing') {
            return response()->json(['status' => 'already_processing']);
        }

        // Marquer comme en cours et dispatcher
        $match->update(['ai_status' => 'processing']);
        \App\Jobs\AnalyzeJobOffer::dispatch($user, $jobOffer, $match);

        if ($request->ajax()) {
            return response()->json(['status' => 'started', 'ai_status' => 'processing']);
        }

        return back()->with('status', 'Analyse IA lancée en arrière-plan.');
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
        if (!$match && $jobOffer->is_detailed) {
            $match = $this->matchingService->match($user, $jobOffer, false, false);
        }

        // Si toujours null (ex: sync échoué), on crée un objet vide pour éviter que la vue ne crashe
        if (!$match) {
            $match = new \App\Models\UserMatch([
                'pre_score' => 0,
                'final_score' => 0,
                'strengths' => [],
                'weaknesses' => []
            ]);
        }

        $hardScore = $this->jobMatcherService->calculateHardScore($user, $jobOffer);

        $isParentFavorite = false;
        if ($jobOffer->metier && $jobOffer->metier->code) {
            $parentCode = substr($jobOffer->metier->code, 0, 5);
            $isParentFavorite = $user->preferredReferentielMetiers()->where('code', $parentCode)->exists();
        }

        // Vérification du statut de blacklist réel
        $isOfferBlacklisted = false;
        if (!$isOfferBlacklisted && $jobOffer->metier_id) {
            $isOfferBlacklisted = $user->preferredMetiers()
                ->where('metier_id', $jobOffer->metier_id)
                ->wherePivot('status', 'refused')
                ->exists();

            if (!$isOfferBlacklisted && $jobOffer->metier->code) {
                $parentCode = substr($jobOffer->metier->code, 0, 5);
                $isOfferBlacklisted = $user->preferredReferentielMetiers()
                    ->where('code', $parentCode)
                    ->wherePivot('status', 'refused')
                    ->exists();
            }
        }

        return view('job-offers.show', compact('jobOffer', 'match', 'user', 'hardScore', 'isParentFavorite', 'isOfferBlacklisted'));
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
