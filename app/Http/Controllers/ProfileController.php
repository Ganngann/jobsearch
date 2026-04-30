<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile.
     */
    public function show(Request $request): View
    {
        return view('profile.show', [
            'user' => $request->user()->load(['skills', 'languages', 'permits']),
        ]);
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'allSkills' => \App\Models\Skill::all(),
            'allLanguages' => \App\Models\Language::all(),
            'allPermits' => \App\Models\Permit::all(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's skills.
     */
    public function updateSkills(Request $request): RedirectResponse
    {
        $request->validate([
            'skills' => ['nullable', 'array'],
            'skills.*' => ['exists:skills,id'],
            'levels' => ['nullable', 'array'],
        ]);

        $syncData = [];
        if ($request->has('skills')) {
            foreach ($request->skills as $skillId) {
                $syncData[$skillId] = ['level' => $request->levels[$skillId] ?? null];
            }
        }

        $request->user()->skills()->sync($syncData);

        return Redirect::route('profile.edit')->with('status', 'skills-updated');
    }

    /**
     * Update the user's languages.
     */
    public function updateLanguages(Request $request): RedirectResponse
    {
        $request->validate([
            'languages' => ['nullable', 'array'],
            'languages.*' => ['exists:languages,id'],
            'lang_levels' => ['nullable', 'array'],
        ]);

        $syncData = [];
        if ($request->has('languages')) {
            foreach ($request->languages as $langId) {
                $syncData[$langId] = ['level' => $request->lang_levels[$langId] ?? null];
            }
        }

        $request->user()->languages()->sync($syncData);

        return Redirect::route('profile.edit')->with('status', 'languages-updated');
    }

    /**
     * Update the user's permits.
     */
    public function updatePermits(Request $request): RedirectResponse
    {
        $request->validate([
            'permits' => ['nullable', 'array'],
            'permits.*' => ['exists:permits,id'],
        ]);

        $request->user()->permits()->sync($request->permits ?? []);

        return Redirect::route('profile.edit')->with('status', 'permits-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
