<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\Skill;
use App\Models\Metier;
use App\Jobs\RecalculateMatchesJob;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    protected $gemini;
    protected $matchingService;
    protected $resumeParser;
    protected $aiService;

    public function __construct(
        \App\Services\GeminiService $gemini, 
        \App\Services\MatchingService $matchingService,
        \App\Services\ResumeParserService $resumeParser,
        \App\Services\AIProfileService $aiService
    ) {
        $this->gemini = $gemini;
        $this->matchingService = $matchingService;
        $this->resumeParser = $resumeParser;
        $this->aiService = $aiService;
    }

    /**
     * Gère l'upload et le parsing d'un CV.
     */
    public function uploadResume(Request $request)
    {
        // Détection spécifique si PHP a rejeté le fichier à cause de sa taille
        if ($request->isMethod('post') && !$request->hasFile('resume') && $request->server('CONTENT_LENGTH') > 0) {
            $maxPhp = ini_get('upload_max_filesize');
            $msg = "Ce fichier est trop lourd (Maximum {$maxPhp}).";
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->withErrors(['resume' => $msg]);
        }

        $request->validate([
            'resume' => 'required|file|max:20480',
        ]);

        $user = Auth::user();
        $file = $request->file('resume');

        // 1. Extraire le texte
        try {
            $text = $this->resumeParser->extractText($file);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['resume' => $e->getMessage()]);
        }

        \Illuminate\Support\Facades\Log::info('CV Upload: Texte extrait (' . strlen($text ?? '') . ' caractères)');

        if (!$text) {
            $msg = 'Impossible d\'extraire le texte de ce fichier.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->withErrors(['resume' => $msg]);
        }

        // 2. Créer une nouvelle session de chat dédiée au CV
        $sessionId = uniqid('cv_');
        \App\Models\ProfileSession::create([
            'id' => $sessionId,
            'user_id' => $user->id,
            'title' => 'Import CV - ' . now()->format('d/m H:i')
        ]);
        
        session(['profile_builder_session' => $sessionId]);

        // 3. Injecter le texte du CV comme premier message utilisateur
        $user->profileMessages()->create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'role' => 'user',
            'content' => "Voici le contenu de mon CV pour analyse :\n\n" . $text
        ]);

        // 4. Lancer l'analyse via le service AI (le même que pour le chat)
        $history = [['role' => 'user', 'content' => $text]];
        $aiResponse = $this->aiService->chat($user, $history);

        \Illuminate\Support\Facades\Log::info('CV Upload: Réponse IA reçue', [
            'has_reply' => isset($aiResponse['reply']),
            'facts_count' => count($aiResponse['facts'] ?? []),
            'exp_count' => count($aiResponse['experiences'] ?? []),
            'raw_keys' => array_keys($aiResponse ?? [])
        ]);

        if ($aiResponse && isset($aiResponse['reply'])) {
            // Sauvegarder la réponse du Coach
            $user->profileMessages()->create([
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'role' => 'assistant',
                'content' => $aiResponse['reply']
            ]);

            // Appliquer les changements (crée les suggestions d'expériences, faits, etc.)
            $this->aiService->processAIChanges($user, $aiResponse, $sessionId);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('profile.builder', ['session' => $sessionId])
            ]);
        }

        // 5. Redirection directe vers le Coach IA pour validation (fallback)
        return redirect()->route('profile.builder', ['session' => $sessionId]);
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
     * Update the user's preferred métiers.
     */

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
