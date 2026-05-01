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
    protected $gemini;

    public function __construct(\App\Services\GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Analyse un texte brut pour générer un profil.
     */
    public function analyze(Request $request)
    {
        $request->validate(['text' => 'required|string|min:50']);

        $suggestion = $this->gemini->analyzeProfile($request->text);

        if (!$suggestion) {
            return response()->json(['error' => 'Échec de l\'analyse IA'], 500);
        }

        return response()->json($suggestion);
    }

    /**
     * Auto-complète le profil à partir des faits validés.
     */
    public function magicFill(Request $request)
    {
        $user = $request->user();
        $facts = $user->facts()->get();

        if ($facts->isEmpty()) {
            return response()->json(['error' => 'Aucun récit trouvé. Discutez avec l\'Assistant pour en ajouter.'], 400);
        }

        $suggestion = $this->gemini->generateProfileFromFacts($facts->toArray());

        if (!$suggestion) {
            return response()->json(['error' => 'Échec de la génération IA'], 500);
        }

        return response()->json($suggestion);
    }
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
    public function updateSkills(Request $request)
    {
        $request->validate([
            'skills' => ['nullable', 'array'],
            'skills.*' => ['exists:skills,id'],
            'levels' => ['nullable', 'array'],
        ]);

        $syncData = [];
        if ($request->has('skills')) {
            foreach ($request->skills as $skillId) {
                $syncData[$skillId] = ['level' => $request->levels[$skillId] ?? 'beginner'];
            }
        }

        $request->user()->skills()->sync($syncData);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Compétences mises à jour']);
        }

        return Redirect::route('profile.edit')->with('status', 'skills-updated');
    }

    /**
     * Update the user's languages.
     */
    public function updateLanguages(Request $request)
    {
        $request->validate([
            'languages' => ['nullable', 'array'],
            'languages.*' => ['exists:languages,id'],
            'levels' => ['nullable', 'array'],
        ]);

        $syncData = [];
        if ($request->has('languages')) {
            foreach ($request->languages as $langId) {
                $syncData[$langId] = ['level' => $request->levels[$langId] ?? null];
            }
        }

        $request->user()->languages()->sync($syncData);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Langues mises à jour']);
        }

        return Redirect::route('profile.edit')->with('status', 'languages-updated');
    }

    /**
     * Update the user's permits.
     */
    public function updatePermits(Request $request)
    {
        $request->validate([
            'permits' => ['nullable', 'array'],
            'permits.*' => ['exists:permits,id'],
        ]);

        $request->user()->permits()->sync($request->permits ?? []);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Permis mis à jour']);
        }

        return Redirect::route('profile.edit')->with('status', 'permits-updated');
    }

    /**
     * Update the user's mobility.
     */
    public function updateMobility(Request $request)
    {
        $validated = $request->validate([
            'zip_code' => ['nullable', 'string', 'max:10'],
            'radius' => ['required', 'integer', 'min:0', 'max:500'],
        ]);

        $request->user()->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Mobilité mise à jour']);
        }

        return Redirect::route('profile.edit')->with('status', 'mobility-updated');
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

    /**
     * Détache une compétence d'un fait spécifique.
     */
    public function detachSkillFromFact(Request $request, \App\Models\UserFact $fact, \App\Models\Skill $skill)
    {
        if ($fact->user_id !== Auth::id()) abort(403);
        
        $fact->skills()->detach($skill->id);

        return response()->json(['success' => true]);
    }

    /**
     * Blackliste une compétence pour l'utilisateur.
     */
    public function blacklistSkill(Request $request, \App\Models\Skill $skill)
    {
        $user = Auth::user();
        
        // Ajouter à la blacklist
        $user->blacklistedSkills()->syncWithoutDetaching([$skill->id]);
        
        // Supprimer de tous les faits
        foreach ($user->facts as $fact) {
            $fact->skills()->detach($skill->id);
        }
        
        // Supprimer du profil global
        $user->skills()->detach($skill->id);

        session()->flash('status', "Compétence '{$skill->label}' ajoutée à la liste noire");

        return response()->json(['success' => true]);
    }

    /**
     * Retire une compétence de la blacklist.
     */
    public function unblacklistSkill(Request $request, \App\Models\Skill $skill)
    {
        $request->user()->blacklistedSkills()->detach($skill->id);

        return response()->json(['success' => true]);
    }

    /**
     * Ajoute une compétence au profil de l'utilisateur.
     */
    public function addSkill(Request $request, \App\Models\Skill $skill)
    {
        Auth::user()->skills()->syncWithoutDetaching([
            $skill->id => ['level' => 'intermediate']
        ]);

        session()->flash('status', "Compétence '{$skill->label}' ajoutée à votre profil");

        return response()->json(['success' => true, 'message' => 'Compétence ajoutée']);
    }

    /**
     * Retire une compétence du profil de l'utilisateur.
     */
    public function removeSkill(Request $request, \App\Models\Skill $skill)
    {
        Auth::user()->skills()->detach($skill->id);

        session()->flash('status', "Compétence '{$skill->label}' retirée de votre profil");

        return response()->json(['success' => true, 'message' => 'Compétence retirée']);
    }
}
