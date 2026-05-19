<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class MobilityController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // On récupère les permis en mettant 'NONE' en haut, puis le reste par label
        $allPermits = Cache::remember('all_permits_ordered', 3600, function () {
            return \App\Models\Permit::orderByRaw("CASE WHEN code = 'NONE' THEN 0 ELSE 1 END")
                ->orderBy('label')
                ->get();
        });
            
        $userPermitIds = $user->permits()->pluck('permits.id')->toArray();
        
        // Liste des types de contrat disponibles (depuis JobOffer)
        $allContractTypes = \App\Models\JobOffer::distinct()->whereNotNull('contract_type')->pluck('contract_type')->sort()->values()->toArray();
        $userContractPreferences = $user->contract_preferences ?? [];

        return view('profile.mobility.index', compact('user', 'allPermits', 'userPermitIds', 'allContractTypes', 'userContractPreferences'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'zip_code' => ['nullable', 'string', 'max:10'],
            'radius' => ['required', 'integer', 'min:0', 'max:500'],
            'permits' => ['nullable', 'array'],
            'permits.*' => ['exists:permits,id'],
            'contract_preferences' => ['nullable', 'array'],
        ]);

        $user = Auth::user();
        $user->update([
            'zip_code' => $request->zip_code,
            'radius' => $request->radius,
            'contract_preferences' => $request->contract_preferences,
        ]);

        // Synchronisation des permis
        $user->permits()->sync($request->permits ?? []);

        // Le recalcul automatique est désactivé pour éviter de saturer la file d'attente.
        // L'utilisateur doit publier ses modifications pour déclencher un nouveau matching global.
        // \App\Jobs\RecalculateMatchesJob::dispatch($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Préférences de mobilité et permis mis à jour.'
        ]);
    }
}