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
use Illuminate\Support\Facades\Cache;

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

        /* 
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
        */

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
            $escapedRome = addcslashes($request->rome, '%_\\');
            $query->whereHas('metier', function($q) use ($escapedRome) {
                $q->where('code', 'LIKE', $escapedRome . '%');
            });
        }
        // 2c. Recherche par mot-clé (Titre ou Employeur)
        if ($request->filled('q')) {
            $escapedQ = addcslashes($request->q, '%_\\');
            $query->where(function($sq) use ($escapedQ) {
                $sq->where('title', 'LIKE', "%{$escapedQ}%")
                   ->orWhereHas('employer', function($eq) use ($escapedQ) {
                       $eq->where('label', 'LIKE', "%{$escapedQ}%");
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
            case 'vector_desc':
                $query->join('user_matches', function($join) use ($user) {
                    $join->on('job_offers.id', '=', 'user_matches.job_offer_id')
                         ->where('user_matches.user_id', '=', $user->id);
                })
                ->select('job_offers.*', 'user_matches.pre_score', 'user_matches.final_score', 'user_matches.vector_score', 'user_matches.ai_score', 'user_matches.pre_score_details')
                ->whereNotNull('user_matches.vector_score')
                ->orderBy('user_matches.vector_score', 'desc');
                break;
            case 'ai_desc':
                $query->join('user_matches', function($join) use ($user) {
                    $join->on('job_offers.id', '=', 'user_matches.job_offer_id')
                         ->where('user_matches.user_id', '=', $user->id);
                })
                ->select('job_offers.*', 'user_matches.pre_score', 'user_matches.final_score', 'user_matches.vector_score', 'user_matches.ai_score', 'user_matches.pre_score_details')
                ->whereNotNull('user_matches.ai_score')
                ->orderBy('user_matches.ai_score', 'desc');
                break;
            case 'score_desc':
            default:
                // Jointure simple sur les scores pré-calculés
                $query->leftJoin('user_matches', function($join) use ($user) {
                    $join->on('job_offers.id', '=', 'user_matches.job_offer_id')
                         ->where('user_matches.user_id', '=', $user->id);
                })
                ->select('job_offers.*', 'user_matches.pre_score', 'user_matches.final_score', 'user_matches.vector_score', 'user_matches.ai_score', 'user_matches.pre_score_details')
                ->orderBy('user_matches.final_score', 'desc')
                ->orderBy('user_matches.pre_score', 'desc');
                break;
        }

        $jobOffers = $query->with(['employer', 'metier', 'userMatch'])->paginate(20);

        $favoriteRomeCodes = $user->preferredReferentielMetiers()->pluck('code')->toArray();

        if ($request->ajax() || $request->has('partial')) {
            return view('job-offers.partials.list-items', compact('jobOffers', 'favoriteRomeCodes'));
        }

        // Données pour les filtres de la sidebar
        // Données pour les filtres de la sidebar : Mise en cache pour 1h
        $topMetiers = Cache::remember('dashboard.top_metiers', 3600, function() {
            // ⚡ Bolt: Selecting only required fields before withCount to prevent memory bloat in cache
            // 📊 Impact: Significantly reduces cache payload size
            return \App\Models\Metier::select(['id', 'label'])->whereHas('jobOffers')
                ->withCount('jobOffers')
                ->orderBy('job_offers_count', 'desc')
                ->limit(100)
                ->get();
        });

        $topEmployers = Cache::remember('dashboard.top_employers', 3600, function() {
            // ⚡ Bolt: Selecting only required fields before withCount to prevent memory bloat in cache
            // 📊 Impact: Significantly reduces cache payload size by omitting large fields like logo_base64
            return \App\Models\Employer::select(['id', 'label'])->whereHas('jobOffers')
                ->withCount('jobOffers')
                ->orderBy('job_offers_count', 'desc')
                ->limit(50)
                ->get();
        });

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
        
        // Autoriser la relance si l'analyse semble bloquée (plus de 10 minutes)
        $isStale = $match->ai_status === 'processing' && $match->updated_at->lt(now()->subMinutes(10));

        if ($match->ai_status === 'processing' && !$isStale) {
            return response()->json([
                'status' => 'already_processing',
                'message' => 'Une analyse est déjà en cours pour cette offre.'
            ]);
        }

        // Marquer comme en cours et dispatcher
        $match->update(['ai_status' => 'processing']);
        
        \Illuminate\Support\Facades\Log::info("Dispatching AnalyzeJobOffer for User #{$user->id} and JobOffer #{$jobOffer->forem_id}");
        
        // Force recalculation of pre-score to ensure details are up-to-date
        $scores = $this->matchingService->calculatePreScore($user, $jobOffer);
        $match->pre_score = $scores['score'];
        $match->pre_score_details = $scores['details'];
        $match->save();

        \App\Jobs\AnalyzeJobOffer::dispatch($user, $jobOffer, $match);

        if ($request->ajax()) {
            return response()->json([
                'status' => 'started', 
                'ai_status' => 'processing',
                'message' => 'L\'analyse IA a été lancée.'
            ]);
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

        // Fallback pour éviter le crash de la vue si le matching échoue
        if (!$match) {
            $match = new \App\Models\UserMatch([
                'pre_score' => 0,
                'final_score' => 0,
                'pre_score_details' => ['base' => 100, 'penalties' => [], 'bonuses' => []],
                'strengths' => [],
                'weaknesses' => []
            ]);
        }

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

        return view('job-offers.show', compact('jobOffer', 'match', 'user', 'isParentFavorite', 'isOfferBlacklisted'));

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

    /**
     * Déclenche l'analyse IA pour les 20 meilleurs matches.
     */
    public function triggerTopAi(Request $request, MatchingService $matchingService): \Illuminate\Http\JsonResponse
    {
        $matchingService->triggerTopAiAnalysis(auth()->user());
        
        return response()->json([
            'message' => 'L\'analyse du Top 20 a été lancée en arrière-plan. Les résultats apparaîtront progressivement.'
        ]);
    }
}


