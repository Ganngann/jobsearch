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
    protected $matchingService;

    public function __construct(\App\Services\GeminiService $gemini, \App\Services\MatchingService $matchingService)
    {
        $this->gemini = $gemini;
        $this->matchingService = $matchingService;
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
            'user' => $request->user()->load(['preferredMetiers', 'blacklistedMetiers']),
            'allSkills' => \App\Models\Skill::all(),
            'allLanguages' => \App\Models\Language::all(),
            'allPermits' => \App\Models\Permit::all(),
            'allMetiers' => \App\Models\Metier::orderBy('label')->get(),
        ]);
    }

    /**
     * Update the user's preferred métiers.
     */
    public function updateMetiers(Request $request)
    {
        $request->validate([
            'metiers' => ['nullable', 'array'],
            'metiers.*' => ['exists:metiers,id'],
        ]);

        $user = $request->user();
        $user->preferredMetiers()->sync($request->metiers ?? []);

        // Démarrage à froid (Asynchrone)
        \App\Jobs\RecalculateMatchesJob::dispatch($user);

        return Redirect::route('profile.edit')->with('status', 'metiers-updated');
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

        // Démarrage à froid (Asynchrone)
        \App\Jobs\RecalculateMatchesJob::dispatch($request->user());

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

        // Recalcul (Asynchrone)
        \App\Jobs\RecalculateMatchesJob::dispatch($request->user());

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

        // Recalcul (Asynchrone)
        \App\Jobs\RecalculateMatchesJob::dispatch($request->user());

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

        // Démarrage à froid (Asynchrone)
        \App\Jobs\RecalculateMatchesJob::dispatch($request->user());

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

        // 1. On traite l'offre actuelle en synchrone pour le feedback immédiat
        if ($request->query('current_offer_id')) {
            $job = \App\Models\JobOffer::find($request->query('current_offer_id'));
            if ($job) {
                $this->matchingService->match(Auth::user(), $job, false, false);
            }
        }

        // 2. On lance le recalcul global en arrière-plan (Debounced/Unique)
        \App\Jobs\RecalculateMatchesJob::dispatch(Auth::user());

        session()->flash('status', "Compétence '{$skill->label}' ajoutée à votre profil");

        return response()->json(['success' => true, 'message' => 'Compétence ajoutée']);
    }

    /**
     * Retire une compétence du profil de l'utilisateur.
     */
    public function removeSkill(Request $request, \App\Models\Skill $skill)
    {
        Auth::user()->skills()->detach($skill->id);

        // 1. On traite l'offre actuelle en synchrone
        if ($request->query('current_offer_id')) {
            $job = \App\Models\JobOffer::find($request->query('current_offer_id'));
            if ($job) {
                $this->matchingService->match(Auth::user(), $job, false, false);
            }
        }

        // 2. Le reste en arrière-plan
        \App\Jobs\RecalculateMatchesJob::dispatch(Auth::user());

        session()->flash('status', "Compétence '{$skill->label}' retirée de votre profil");

        return response()->json(['success' => true, 'message' => 'Compétence retirée']);
    }

    /**
     * Blackliste un métier pour l'utilisateur.
     */
    public function blacklistMetier(Request $request, \App\Models\Metier $metier)
    {
        $user = Auth::user();
        
        // Ajouter à la blacklist
        $user->blacklistedMetiers()->syncWithoutDetaching([$metier->id]);
        
        // Supprimer des métiers préférés
        $user->preferredMetiers()->detach($metier->id);
        
        // Optionnel : Supprimer les matches existants pour ce métier
        \App\Models\UserMatch::where('user_id', $user->id)
            ->whereHas('jobOffer', function($q) use ($metier) {
                $q->where('metier_id', $metier->id);
            })->delete();

        session()->flash('status', "Métier '{$metier->label}' ajouté à la liste noire");

        return response()->json(['success' => true]);
    }

    /**
     * Retire un métier des favoris de l'utilisateur.
     */
    public function removeMetier(Request $request, \App\Models\Metier $metier)
    {
        $user = Auth::user();
        $user->preferredMetiers()->detach($metier->id);

        $this->matchingService->triggerMetierMatch($user, $metier->id);

        return response()->json(['success' => true]);
    }

    /**
     * Ajoute un métier aux favoris de l'utilisateur.
     */
    public function addMetier(Request $request, \App\Models\Metier $metier)
    {
        $user = Auth::user();
        
        $user->preferredMetiers()->syncWithoutDetaching([$metier->id]);
        $user->blacklistedMetiers()->detach($metier->id);

        // Déclencher un matching pour ce nouvel intérêt (uniquement sur ce métier)
        $this->matchingService->triggerMetierMatch($user, $metier->id);

        return response()->json(['success' => true]);
    }

    /**
     * Retire un métier de la blacklist.
     */
    public function unblacklistMetier(Request $request, \App\Models\Metier $metier)
    {
        $request->user()->blacklistedMetiers()->detach($metier->id);
        
        $this->matchingService->triggerMetierMatch($request->user(), $metier->id);

        return response()->json(['success' => true]);
    }

    /**
     * Recherche de métiers par mot-clé (API).
     */
    /**
     * Recherche de métiers par mot-clé (API).
     */
    public function searchMetiers(Request $request)
    {
        $q = $request->query('q');
        if (!$q || strlen($q) < 2) return response()->json([]);

        $metiers = \App\Models\Metier::where('label', 'like', "%{$q}%")
            ->orWhere('code', 'like', "%{$q}%")
            ->limit(50) // Augmenté pour plus de visibilité
            ->orderBy('label')
            ->get(['id', 'label', 'code']);

        return response()->json($metiers);
    }

    /**
     * Recherche de compétences par mot-clé (API).
     */
    public function searchSkills(Request $request)
    {
        $q = $request->query('q');
        if (!$q || strlen($q) < 2) return response()->json([]);

        $skills = \App\Models\Skill::where('label', 'like', "%{$q}%")
            ->limit(20)
            ->orderBy('label')
            ->get(['id', 'label']);

        return response()->json($skills);
    }
}
