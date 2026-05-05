<?php

namespace App\Http\Controllers;

use App\Services\DiscoveryService;
use App\Services\MatchingService;
use App\Models\ReferentielMetier;
use App\Models\Metier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiscoveryController extends Controller
{
    protected $discovery;
    protected $matcher;

    public function __construct(DiscoveryService $discovery, MatchingService $matcher)
    {
        $this->discovery = $discovery;
        $this->matcher = $matcher;
    }

    public function index()
    {
        $user = Auth::user();
        $suggestions = $user->discoverySuggestions()->get()->map(function($s) use ($user) {
            $referentiel = ReferentielMetier::where('code', $s->code)->first();
            $pivot = $user->preferredReferentielMetiers()->where('code', $s->code)->first();
            $isParentFavorite = $pivot && $pivot->pivot->status === 'favorite';
            $parentStatus = $pivot ? $pivot->pivot->status : 'none';
            $isParentRefused = $pivot && $pivot->pivot->status === 'refused';

            $variants = Metier::where('code', 'LIKE', $s->code . '%')
                ->orderBy('label')
                ->get(['id', 'code', 'label'])
                ->map(function($v) use ($user, $isParentFavorite) {
                    $pivot = $user->preferredMetiers()->where('metier_id', $v->id)->first();
                    $v->status = $pivot ? $pivot->pivot->status : ($isParentFavorite ? 'favorite' : 'none');
                    return $v;
                });
            
            $offersCount = \App\Models\JobOffer::whereHas('metier', function($q) use ($s) {
                $q->where('code', 'LIKE', $s->code . '%');
            })->count();
            
            return [
                'id' => $referentiel ? $referentiel->id : null,
                'code' => $s->code,
                'title' => $s->title,
                'reason' => $s->reason,
                'type' => $s->type,
                'status' => $parentStatus,
                'is_favorite' => $isParentFavorite,
                'is_refused' => $isParentRefused,
                'variants' => $variants,
                'offers_count' => $offersCount
            ];
        });

        $savedReferentiels = $user->preferredReferentielMetiers()
            ->get(['referentiel_metiers.id', 'code', 'title'])
            ->map(fn($r) => [
                'id' => $r->id,
                'code' => $r->code,
                'title' => $r->title,
                'type' => 'family',
                'status' => $r->pivot->status
            ]);

        $savedMetiers = $user->preferredMetiers()
            ->get(['metiers.id', 'code', 'label'])
            ->map(fn($m) => [
                'id' => $m->id,
                'code' => $m->code,
                'title' => $m->label,
                'type' => 'specific',
                'status' => $m->pivot->status
            ]);

        return view('discovery.index', [
            'initialSuggestions' => $suggestions,
            'savedMetiers' => $savedReferentiels->concat($savedMetiers)
        ]);
    }

    public function suggest()
    {
        $user = Auth::user();
        $aiSuggestions = $this->discovery->suggestMetiers($user);
        
        if (empty($aiSuggestions)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Le service de suggestion est temporairement indisponible (Gemini est surchargé). Veuillez réessayer dans quelques instants.',
                'suggestions' => []
            ], 503);
        }

        // Supprimer les anciennes suggestions pour ce user (on garde les 3 fraîches)
        $user->discoverySuggestions()->delete();

        // Persister les nouvelles
        foreach ($aiSuggestions as $s) {
            $user->discoverySuggestions()->create([
                'code' => $s['code'],
                'title' => $s['title'],
                'reason' => $s['reason'],
                'type' => $s['type'] ?? 'aligned',
            ]);
        }

        // Récupérer avec l'état des favoris et blacklist
        $favoriteCodes = $user->preferredReferentielMetiers()->pluck('code')->toArray();
        
        $enriched = array_map(function($s) use ($user) {
            $referentiel = ReferentielMetier::where('code', $s['code'])->first();
            $pivot = $user->preferredReferentielMetiers()->where('code', $s['code'])->first();
            $isParentFavorite = $pivot && $pivot->pivot->status === 'favorite';
            $parentStatus = $pivot ? $pivot->pivot->status : 'none';
            $isParentRefused = $pivot && $pivot->pivot->status === 'refused';

            $s['id'] = $referentiel ? $referentiel->id : null;
            $s['status'] = $parentStatus;
            $s['is_favorite'] = $isParentFavorite;
            $s['is_refused'] = $isParentRefused;
            
            $s['variants'] = Metier::where('code', 'LIKE', $s['code'] . '%')
                ->orderBy('label')
                ->get(['id', 'code', 'label'])
                ->map(function($v) use ($user, $isParentFavorite) {
                    $pivot = $user->preferredMetiers()->where('metier_id', $v->id)->first();
                    $v->status = $pivot ? $pivot->pivot->status : ($isParentFavorite ? 'favorite' : 'none');
                    return $v;
                });

            $s['offers_count'] = \App\Models\JobOffer::whereHas('metier', function($q) use ($s) {
                $q->where('code', 'LIKE', $s['code'] . '%');
            })->count();

            return $s;
        }, $aiSuggestions);
        
        return response()->json([
            'suggestions' => $enriched
        ]);
    }

    public function setReferentielStatus($code, Request $request)
    {
        $user = Auth::user();
        $status = $request->input('status'); // favorite, neutral, refused, none

        $referentiel = ReferentielMetier::firstOrCreate(
            ['code' => $code],
            ['title' => $request->input('title') ?? 'Domaine ' . $code]
        );

        if ($status === 'none') {
            $user->preferredReferentielMetiers()->detach($referentiel->id);
        } else {
            $user->preferredReferentielMetiers()->syncWithPivotValues([$referentiel->id], ['status' => $status], false);
        }

        // Recalculer les scores pour toute la famille
        $this->matcher->triggerRomeMatch($user, $referentiel->code);

        return response()->json([
            'status' => 'success',
            'current_status' => $status
        ]);
    }

    public function setMetierStatus(Metier $metier, Request $request)
    {
        $user = Auth::user();
        $status = $request->input('status'); // favorite, neutral, none

        if ($status === 'none') {
            $user->preferredMetiers()->detach($metier->id);
        } else {
            $user->preferredMetiers()->syncWithPivotValues([$metier->id], ['status' => $status], false);
        }

        // Recalculer les scores pour ce métier
        $this->matcher->triggerRomeMatch($user, $metier->code);

        return response()->json([
            'status' => 'success',
            'current_status' => $status
        ]);
    }

    public function children($code)
    {
        $user = Auth::user();
        $isParentFavorite = $user->preferredReferentielMetiers()->where('code', $code)->exists();

        $metiers = Metier::where('code', 'LIKE', $code . '%')
            ->orderBy('label')
            ->get(['id', 'code', 'label']);
            
        return response()->json([
            'is_parent_favorite' => $isParentFavorite,
            'metiers' => $metiers
        ]);
    }
}
