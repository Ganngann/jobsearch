<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobilityController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('profile.mobility.index', compact('user'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'zip_code' => ['nullable', 'string', 'max:10'],
            'radius' => ['required', 'integer', 'min:0', 'max:500'],
        ]);

        $user = Auth::user();
        $user->update([
            'zip_code' => $request->zip_code,
            'radius' => $request->radius,
        ]);

        // Déclenchement du recalcul des matchs
        \App\Jobs\RecalculateMatchesJob::dispatch($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Préférences de mobilité mises à jour.'
        ]);
    }
}
