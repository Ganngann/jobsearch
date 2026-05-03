<?php

namespace App\Http\Controllers;

use App\Models\Experience;
use App\Models\Education;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserJourneyController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $experiences = $user->experiences;
        $educations = $user->educations;

        return view('profile.journey', compact('experiences', 'educations'));
    }

    public function storeExperience(Request $request)
    {
        $validated = $request->validate([
            'company' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_current' => 'boolean',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['is_current'] = $request->has('is_current');

        Experience::create($validated);

        return back()->with('success', 'Expérience ajoutée avec succès.');
    }

    public function deleteExperience(Experience $experience)
    {
        if ($experience->user_id !== Auth::id()) abort(403);
        $experience->delete();
        return back()->with('success', 'Expérience supprimée.');
    }

    public function storeEducation(Request $request)
    {
        $validated = $request->validate([
            'school' => 'required|string|max:255',
            'degree' => 'required|string|max:255',
            'field' => 'nullable|string|max:255',
            'graduation_year' => 'nullable|integer|min:1900|max:' . (date('Y') + 10),
            'description' => 'nullable|string',
        ]);

        $validated['user_id'] = Auth::id();

        Education::create($validated);

        return back()->with('success', 'Formation ajoutée avec succès.');
    }

    public function deleteEducation(Education $education)
    {
        if ($education->user_id !== Auth::id()) abort(403);
        $education->delete();
        return back()->with('success', 'Formation supprimée.');
    }

    public function validateExperience(Experience $experience)
    {
        if ($experience->user_id !== Auth::id()) abort(403);
        $experience->update(['status' => 'validated', 'proposed_action' => null]);
        return back()->with('success', 'Expérience validée.');
    }

    public function validateEducation(Education $education)
    {
        if ($education->user_id !== Auth::id()) abort(403);
        $education->update(['status' => 'validated', 'proposed_action' => null]);
        return back()->with('success', 'Formation validée.');
    }
}
