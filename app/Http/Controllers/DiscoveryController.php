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
            $favoriteCodes = $user->preferredReferentielMetiers()->pluck('code')->toArray();
            $blacklistedCodes = $user->blacklistedReferentielMetiers()->pluck('code')->toArray();
            $favoriteMetierIds = $user->preferredMetiers()->pluck('metiers.id')->toArray();
            $isParentFavorite = in_array($s->code, $favoriteCodes);

            $blacklistedMetierIds = $user->blacklistedMetiers()->pluck('metiers.id')->toArray();

            $variants = Metier::where('code', 'LIKE', $s->code . '%')
                ->orderBy('label')
                ->get(['id', 'code', 'label'])
                ->map(function($v) use ($favoriteMetierIds, $blacklistedMetierIds, $isParentFavorite) {
                    $v->is_favorite = $isParentFavorite || in_array($v->id, $favoriteMetierIds);
                    $v->is_blacklisted = in_array($v->id, $blacklistedMetierIds);
                    return $v;
                });
            
            return [
                'code' => $s->code,
                'title' => $s->title,
                'reason' => $s->reason,
                'type' => $s->type,
                'is_favorite' => $isParentFavorite,
                'is_blacklisted' => in_array($s->code, $blacklistedCodes),
                'variants' => $variants
            ];
        });

        return view('discovery.index', [
            'initialSuggestions' => $suggestions
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
        $blacklistedCodes = $user->blacklistedReferentielMetiers()->pluck('code')->toArray();
        $favoriteMetierIds = $user->preferredMetiers()->pluck('metiers.id')->toArray();
        $blacklistedMetierIds = $user->blacklistedMetiers()->pluck('metiers.id')->toArray();
        
        $enriched = array_map(function($s) use ($favoriteCodes, $blacklistedCodes, $favoriteMetierIds, $blacklistedMetierIds) {
            $isParentFavorite = in_array($s['code'], $favoriteCodes);
            $s['is_favorite'] = $isParentFavorite;
            $s['is_blacklisted'] = in_array($s['code'], $blacklistedCodes);
            
            $s['variants'] = Metier::where('code', 'LIKE', $s['code'] . '%')
                ->orderBy('label')
                ->get(['id', 'code', 'label'])
                ->map(function($v) use ($favoriteMetierIds, $blacklistedMetierIds, $isParentFavorite) {
                    $v->is_favorite = $isParentFavorite || in_array($v->id, $favoriteMetierIds);
                    $v->is_blacklisted = in_array($v->id, $blacklistedMetierIds);
                    return $v;
                });

            return $s;
        }, $aiSuggestions);
        
        return response()->json([
            'suggestions' => $enriched
        ]);
    }

    public function toggleFavorite(ReferentielMetier $referentiel)
    {
        $user = Auth::user();
        $user->preferredReferentielMetiers()->toggle($referentiel->id);
        
        // Recalculer les scores pour toute la famille ROME
        $this->matcher->triggerRomeMatch($user, $referentiel->code);

        return response()->json([
            'status' => 'success',
            'is_favorite' => $user->preferredReferentielMetiers()->where('referentiel_metier_id', $referentiel->id)->exists()
        ]);
    }

    public function toggleBlacklist(ReferentielMetier $referentiel)
    {
        $user = Auth::user();
        $user->blacklistedReferentielMetiers()->toggle($referentiel->id);
        
        // Supprimer des favoris si on blacklist
        $user->preferredReferentielMetiers()->detach($referentiel->id);

        // Recalculer les scores pour toute la famille ROME (devraient passer à 0)
        $this->matcher->triggerRomeMatch($user, $referentiel->code);

        return response()->json([
            'status' => 'success',
            'is_blacklisted' => $user->blacklistedReferentielMetiers()->where('referentiel_metier_id', $referentiel->id)->exists()
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
