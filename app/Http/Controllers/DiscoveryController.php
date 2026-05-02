<?php

namespace App\Http\Controllers;

use App\Services\DiscoveryService;
use App\Models\ReferentielMetier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiscoveryController extends Controller
{
    protected $discovery;

    public function __construct(DiscoveryService $discovery)
    {
        $this->discovery = $discovery;
    }

    public function index()
    {
        $user = Auth::user();
        $suggestions = $user->discoverySuggestions()->get()->map(function($s) use ($user) {
            $favoriteCodes = $user->preferredReferentielMetiers()->pluck('code')->toArray();
            return [
                'code' => $s->code,
                'title' => $s->title,
                'reason' => $s->reason,
                'type' => $s->type,
                'is_favorite' => in_array($s->code, $favoriteCodes)
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

        // Récupérer avec l'état des favoris
        $favoriteCodes = $user->preferredReferentielMetiers()->pluck('code')->toArray();
        $enriched = array_map(function($s) use ($favoriteCodes) {
            $s['is_favorite'] = in_array($s['code'], $favoriteCodes);
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
        
        return response()->json([
            'status' => 'success',
            'is_favorite' => $user->preferredReferentielMetiers()->where('referentiel_metier_id', $referentiel->id)->exists()
        ]);
    }
}
