<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobilityController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // On récupère les permis en mettant 'NONE' en haut, puis le reste par label
        $allPermits = \App\Models\Permit::orderByRaw("CASE WHEN code = 'NONE' THEN 0 ELSE 1 END")
            ->orderBy('label')
            ->get();
            
        $userPermitIds = $user->permits()->pluck('permits.id')->toArray();
        
        return view('profile.mobility.index', compact('user', 'allPermits', 'userPermitIds'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'zip_code' => ['nullable', 'string', 'max:10'],
            'radius' => ['required', 'integer', 'min:0', 'max:500'],
            'permits' => ['nullable', 'array'],
            'permits.*' => ['exists:permits,id'],
        ]);

        $user = Auth::user();
        $user->update([
            'zip_code' => $request->zip_code,
            'radius' => $request->radius,
        ]);

        // Synchronisation des permis
        $user->permits()->sync($request->permits ?? []);

        // Déclenchement du recalcul des matchs
        \App\Jobs\RecalculateMatchesJob::dispatch($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Préférences de mobilité et permis mis à jour.'
        ]);
    }
}
