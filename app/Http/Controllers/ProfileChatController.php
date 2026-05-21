<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserFact;
use App\Models\ProfileMessage;
use App\Models\Experience;
use App\Models\Education;
use App\Services\AIProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProfileChatController extends Controller
{
    protected AIProfileService $aiService;

    public function __construct(AIProfileService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Synchronisation des sessions existantes si besoin
        $existingSessionIds = $user->profileMessages()->distinct()->pluck('session_id');
        foreach ($existingSessionIds as $sid) {
            \App\Models\ProfileSession::firstOrCreate(
                ['id' => $sid],
                ['user_id' => $user->id, 'title' => 'Ancienne discussion']
            );
        }

        $activeSessions = $user->profileSessions()
            ->where('is_archived', false)
            ->get();
            
        $archivedSessions = $user->profileSessions()
            ->where('is_archived', true)
            ->get();

        $sessionId = $request->query('session') ?? session('profile_builder_session');

        if (!$sessionId) {
            $sessionId = $activeSessions->first()?->id ?? uniqid();
        }
        
        session(['profile_builder_session' => $sessionId]);

        $messages = $user->profileMessages()->where('session_id', $sessionId)->orderBy('created_at', 'asc')->get();

        if ($messages->isEmpty()) {
            $aiResponse = $this->aiService->generateOpeningMessage($user);
            
            // On traite les changements (faits, etc.) dès l'ouverture si l'IA en propose
            $this->aiService->processAIChanges($user, $aiResponse, $sessionId);

            $user->profileMessages()->create([
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'role' => 'assistant',
                'content' => $aiResponse['reply'] ?? "Bonjour {$user->name}. Parlons de ton parcours."
            ]);
            // Re-fetch messages
            $messages = $user->profileMessages()->where('session_id', $sessionId)->orderBy('created_at', 'asc')->get();
        }

        $facts = $user->facts()
            ->orderByRaw('CASE WHEN proposed_action IS NOT NULL THEN 0 ELSE 1 END')
            ->orderBy('updated_at', 'desc')
            ->get();

        // Statistiques de profondeur (Calcul pondéré)
        $categoryCounts = [
            'VALEURS' => $facts->where('category', 'VALEURS')->count(),
            'OBJECTIFS' => $facts->where('category', 'OBJECTIFS')->count(),
            'SOFT_SKILLS' => $facts->where('category', 'SOFT_SKILLS')->count(),
            'PREFERENCES' => $facts->where('category', 'PREFERENCES')->count(),
        ];

        // Score Narratif (70%) : On veut ~5 faits par catégorie (total 20)
        $narrativeScore = 0;
        foreach ($categoryCounts as $count) {
            $narrativeScore += min(5, $count); // Max 5 points par catégorie
        }
        $narrativePercentage = ($narrativeScore / 20) * 70;

        // Score Parcours (30%) : On veut au moins 3 éléments (Exp/Edu)
        $journeyCount = $user->experiences()->count() + $user->educations()->count();
        $journeyPercentage = min(3, $journeyCount) / 3 * 30;

        $totalDepth = round($narrativePercentage + $journeyPercentage);

        $stats = [
            'total_facts' => $facts->count(),
            'depth_percentage' => $totalDepth,
            'categories' => [
                'VALEURS' => ['current' => $categoryCounts['VALEURS'], 'target' => 5],
                'OBJECTIFS' => ['current' => $categoryCounts['OBJECTIFS'], 'target' => 5],
                'SOFT_SKILLS' => ['current' => $categoryCounts['SOFT_SKILLS'], 'target' => 5],
                'PREFERENCES' => ['current' => $categoryCounts['PREFERENCES'], 'target' => 5],
            ],
            'journey' => [
                'current' => $journeyCount,
                'target' => 3,
                'experiences' => $user->experiences()->count(),
                'educations' => $user->educations()->count(),
            ]
        ];

        $projects = $user->projects()->orderBy('updated_at', 'desc')->get();
        $certifications = $user->certifications()->orderBy('updated_at', 'desc')->get();
        $interests = $user->interests()->orderBy('updated_at', 'desc')->get();
        $volunteer_experiences = $user->volunteerExperiences()->orderBy('updated_at', 'desc')->get();
        $all_experiences = $user->experiences()->orderBy('start_date', 'desc')->get();
        $all_educations = $user->educations()->orderBy('graduation_year', 'desc')->get();
        $languages = $user->languages()->get()->map(fn($l) => ['id' => $l->id, 'label' => $l->label, 'level' => $l->pivot->level]);
        $allAvailableLanguages = Cache::remember('all_languages_ordered', 3600, function () { return \App\Models\Language::orderBy('label')->get(['id', 'label']); });

        return view('profile.builder', compact(
            'messages', 'facts', 'activeSessions', 'archivedSessions', 'sessionId', 'stats',
            'projects', 'certifications', 'interests', 'volunteer_experiences',
            'all_experiences', 'all_educations', 'languages', 'allAvailableLanguages'
        ));
    }

    public function resetSession()
    {
        $newSessionId = uniqid();
        session(['profile_builder_session' => $newSessionId]);
        return redirect()->route('profile.builder');
    }

    public function uploadDocument(Request $request, \App\Services\ResumeParserService $resumeParser)
    {
        // SECURE: Restricting file uploads to supported types (mimes:pdf,docx) to prevent malicious files
        $request->validate(['document' => 'required|file|mimes:pdf,docx|max:20480']);
        $user = Auth::user();
        $sessionId = session('profile_builder_session');
        
        if (!$sessionId) {
            return response()->json(['error' => 'No active session'], 400);
        }

        $file = $request->file('document');
        try {
            $text = $resumeParser->extractText($file);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        if (!$text) {
            return response()->json(['error' => 'Impossible d\'extraire le texte du document'], 422);
        }

        // Ajouter le message utilisateur
        $user->profileMessages()->create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'role' => 'user',
            'content' => "J'ajoute un nouveau document pour analyse :\n\n" . substr($text, 0, 1000) . (strlen($text) > 1000 ? '...' : '')
        ]);

        $history = \App\Models\ProfileMessage::where('session_id', $sessionId)
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get()
            ->sortBy('id')
            ->values()
            ->map(fn($m) => ['role' => $m->role, 'content' => $m->content])
            ->toArray();

        // Analyse complète (on passe tout le texte à l'IA même si on tronque le message affiché)
        $history[count($history) - 1]['content'] = "Voici un nouveau document à analyser :\n\n" . $text;

        try {
            $aiResponse = $this->aiService->chat($user, $history);
        } catch (\Exception $e) {
            Log::error('AI Chat Upload Error: ' . $e->getMessage());
            $errorMsg = "L'analyse du document a pris trop de temps. Le document est bien reçu, mais l'IA n'a pas pu finir sa lecture. Peux-tu essayer de relancer la discussion ?";
            return $this->getUpdatedDataResponse($user, $sessionId, $errorMsg);
        }

        if ($aiResponse && isset($aiResponse['reply'])) {
            $user->profileMessages()->create([
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'role' => 'assistant',
                'content' => $aiResponse['reply']
            ]);

            $this->aiService->processAIChanges($user, $aiResponse, $sessionId);
        }

        return $this->getUpdatedDataResponse($user, $sessionId, $aiResponse['reply'] ?? "Document analysé.");
    }

    private function getUpdatedDataResponse($user, $sessionId, $reply)
    {
        $user = $user->fresh();
        $facts = $user->facts;
        $categoryCounts = [
            'VALEURS' => 0,
            'OBJECTIFS' => 0,
            'SOFT_SKILLS' => 0,
            'PREFERENCES' => 0,
        ];
        foreach ($facts as $fact) {
            $categoryCounts[$fact->category] = ($categoryCounts[$fact->category] ?? 0) + 1;
        }

        $narrativeScore = 0;
        foreach ($categoryCounts as $count) {
            $narrativeScore += min(5, $count);
        }
        $narrativePercentage = ($narrativeScore / 20) * 70;

        $journeyCount = $user->experiences()->count() + $user->educations()->count();
        $journeyPercentage = min(3, $journeyCount) / 3 * 30;

        $totalDepth = round($narrativePercentage + $journeyPercentage);

        $stats = [
            'total_facts' => $facts->count(),
            'depth_percentage' => $totalDepth,
            'categories' => [
                'VALEURS' => ['current' => $categoryCounts['VALEURS'], 'target' => 5],
                'OBJECTIFS' => ['current' => $categoryCounts['OBJECTIFS'], 'target' => 5],
                'SOFT_SKILLS' => ['current' => $categoryCounts['SOFT_SKILLS'], 'target' => 5],
                'PREFERENCES' => ['current' => $categoryCounts['PREFERENCES'], 'target' => 5],
            ],
            'journey' => [
                'current' => $journeyCount,
                'target' => 3,
                'experiences' => $user->experiences()->count(),
                'educations' => $user->educations()->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'reply' => $reply,
            'user' => $user->fresh(),
            'stats' => $stats,
            'facts' => $user->facts()
                ->orderByRaw('CASE WHEN proposed_action IS NOT NULL THEN 0 ELSE 1 END')
                ->orderBy('updated_at', 'desc')
                ->get(),
            'projects' => $user->projects()->orderBy('updated_at', 'desc')->get(),
            'certifications' => $user->certifications()->orderBy('updated_at', 'desc')->get(),
            'interests' => $user->interests()->orderBy('updated_at', 'desc')->get(),
            'volunteer_experiences' => $user->volunteerExperiences()->orderBy('updated_at', 'desc')->get(),
            'all_experiences' => $user->experiences()->orderBy('start_date', 'desc')->get(),
            'all_educations' => $user->educations()->orderBy('graduation_year', 'desc')->get(),
            'languages' => $user->languages()->get()->map(fn($l) => ['id' => $l->id, 'label' => $l->label, 'level' => $l->pivot->level]),
            'skills' => $user->skills()->get(),
            'activeSessions' => $user->profileSessions()->where('is_archived', false)->get(),
            'archivedSessions' => $user->profileSessions()->where('is_archived', true)->get()
        ]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate(['message' => 'required|string|max:10000']);
        $user = Auth::user();
        $sessionId = session('profile_builder_session', uniqid());

        $session = \App\Models\ProfileSession::firstOrCreate(
            ['id' => $sessionId],
            ['user_id' => $user->id, 'title' => substr($request->message, 0, 50) . '...']
        );

        $userMsg = $user->profileMessages()->create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'role' => 'user',
            'content' => $request->message
        ]);

        Log::debug('SAVED USER MESSAGE:', ['id' => $userMsg->id, 'content' => $userMsg->content, 'session' => $sessionId]);

        $user->refresh(); // Force la recharge de l'utilisateur et ses relations

        // Validation automatique des changements en attente avant de traiter le nouveau message
        $this->autoValidatePendingChanges($user);
        
        // On rafraîchit les relations pour que buildContext() voit les données validées
        $user->unsetRelations();

        if ($session->messages()->count() === 1) {
            $session->update(['title' => mb_substr($request->message, 0, 50) . '...']);
        }
        $session->touch();
        $user->refresh();

        $history = \App\Models\ProfileMessage::where('session_id', $sessionId)
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get()
            ->sortBy('id')
            ->values()
            ->map(fn($m) => ['role' => $m->role, 'content' => $m->content])
            ->toArray();

        try {
            $aiResponse = $this->aiService->chat($user, $history);
        } catch (\Exception $e) {
            Log::error('AI Chat Error: ' . $e->getMessage());
            $errorMsg = "L'IA met trop de temps à répondre (Timeout). Cela arrive parfois lors de pics de charge. Peux-tu réessayer d'envoyer ton message ?";
            
            // On sauvegarde l'erreur dans la conversation pour la transparence
            $user->profileMessages()->create([
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'role' => 'assistant',
                'content' => "⚠️ [Erreur Système] $errorMsg"
            ]);

            return $this->getUpdatedDataResponse($user, $sessionId, $errorMsg);
        }
        
        Log::debug('--- AI OUTPUT (RAW RESPONSE) ---', ['data' => $aiResponse]);

        if (!$aiResponse || !isset($aiResponse['reply'])) {
            return response()->json([
                'reply' => "Désolé, j'ai eu un petit souci pour traiter votre message.",
                'facts' => $user->facts()->withCount('skills')->orderBy('skills_count', 'desc')->get()
            ]);
        }

        $user->profileMessages()->create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'role' => 'assistant',
            'content' => $aiResponse['reply']
        ]);

        // Traitement des données extraites par l'IA (Faits, Expériences, etc.) via le service unifié
        $this->aiService->processAIChanges($user, $aiResponse, $sessionId);

        $user = $user->fresh();

        return $this->getUpdatedDataResponse($user, $sessionId, $aiResponse['reply']);
    }

    /**
     * Tente de parser une date et renvoie le format Y-m-d ou la valeur par défaut.
     */
    private function sanitizeDate($date, $default = null)
    {
        if (!$date) return $default;
        try {
            return \Illuminate\Support\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Extrait un entier d'une année potentiellement sous forme de texte.
     */
    private function sanitizeYear($year)
    {
        if (!$year) return null;
        $clean = preg_replace('/[^0-9]/', '', (string)$year);
        return $clean ? (int)$clean : null;
    }

    public function toggleArchive(Request $request, $sessionId)
    {
        $session = \App\Models\ProfileSession::where('id', $sessionId)->where('user_id', Auth::id())->firstOrFail();
        $session->update(['is_archived' => !$session->is_archived]);
        return response()->json(['success' => true]);
    }


    public function updateFact(Request $request, UserFact $fact)
    {
        if ($fact->user_id !== Auth::id()) abort(403);

        $request->validate([
            'content' => 'required|string|max:10000',
        ]);

        $fact->update(['content' => $request->content, 'status' => 'validated']);
        return $this->getUpdatedDataResponse(Auth::user(), session('profile_builder_session'), "Fait mis à jour.");
    }

    public function validateFact(UserFact $fact)
    {
        if ($fact->user_id !== Auth::id()) abort(403);
        $fact->update(['status' => 'validated', 'proposed_action' => null]);
        return $this->getUpdatedDataResponse(Auth::user(), session('profile_builder_session'), "Fait validé.");
    }

    public function acceptProposal(\App\Models\UserFact $fact)
    {
        if ($fact->user_id !== Auth::id()) abort(403);

        if ($fact->proposed_action === 'update') {
            $fact->update([
                'content' => $fact->proposed_content,
                'proposed_content' => null,
                'proposed_action' => null,
                'status' => 'validated'
            ]);
        } elseif ($fact->proposed_action === 'add') {
            $fact->update([
                'proposed_action' => null,
                'status' => 'validated'
            ]);
        } elseif ($fact->proposed_action === 'delete') {
            $fact->delete();
        }
        
        return $this->getUpdatedDataResponse(Auth::user(), session('profile_builder_session'), "Suggestion acceptée.");
    }

    public function rejectProposal(\App\Models\UserFact $fact)
    {
        if ($fact->user_id !== Auth::id()) abort(403);

        if ($fact->proposed_action === 'add') {
            $fact->delete();
        } else {
            $fact->update([
                'proposed_content' => null,
                'proposed_action' => null
            ]);
        }

        return $this->getUpdatedDataResponse(Auth::user(), session('profile_builder_session'), "Suggestion rejetée.");
    }

    public function storeItem(Request $request, $type)
    {
        $user = Auth::user();

        $rules = match ($type) {
            'experience' => [
                'company' => 'required|string|max:255',
                'company_logo' => 'nullable|string|max:255',
                'title' => 'required|string|max:255',
                'employment_type' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'location' => 'nullable|string|max:255',
                'start_date' => 'nullable',
                'end_date' => 'nullable',
                'is_current' => 'nullable|boolean',
            ],
            'education' => [
                'school' => 'required|string|max:255',
                'degree' => 'nullable|string|max:255',
                'field' => 'nullable|string|max:255',
                'start_date' => 'nullable',
                'graduation_year' => 'nullable',
                'grade' => 'nullable|string|max:255',
                'description' => 'nullable|string',
            ],
            'project' => [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'url' => 'nullable|string|max:255',
                'start_date' => 'nullable',
                'end_date' => 'nullable',
                'is_ongoing' => 'nullable|boolean',
            ],
            'interest' => [
                'name' => 'required|string|max:255',
            ],
            'certification' => [
                'name' => 'required|string|max:255',
                'issuing_organization' => 'nullable|string|max:255',
                'issue_date' => 'nullable',
                'expiration_date' => 'nullable',
                'credential_id' => 'nullable|string|max:255',
                'credential_url' => 'nullable|string|max:255',
            ],
            'volunteer' => [
                'organization' => 'required|string|max:255',
                'role' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'nullable',
                'end_date' => 'nullable',
            ],
            'fact' => [
                'content' => 'required|string|max:10000',
                'category' => 'nullable|string|max:255',
                'experience_id' => 'nullable|exists:experiences,id',
                'session_id' => 'nullable|string|max:255',
            ],
            'skill' => [
                'label' => 'required|string|max:255',
            ],
            'language' => [
                'label' => 'required|string|max:255',
                'level' => 'nullable|string|max:255',
            ],
            default => null
        };

        if (!$rules) return response()->json(['error' => 'Invalid type'], 400);

        $data = $request->validate($rules);
        $data['status'] = 'validated'; // Manual addition is pre-validated

        // Sanitize dates if present
        if (isset($data['start_date'])) $data['start_date'] = $this->sanitizeDate($data['start_date']);
        if (isset($data['end_date'])) $data['end_date'] = $this->sanitizeDate($data['end_date']);
        if (isset($data['issue_date'])) $data['issue_date'] = $this->sanitizeDate($data['issue_date']);
        if (isset($data['expiration_date'])) $data['expiration_date'] = $this->sanitizeDate($data['expiration_date']);

        $item = match ($type) {
            'experience' => $user->experiences()->create($data),
            'education' => $user->educations()->create($data),
            'project' => $user->projects()->create($data),
            'interest' => $user->interests()->create($data),
            'certification' => $user->certifications()->create($data),
            'volunteer' => $user->volunteerExperiences()->create($data),
            'fact' => $user->facts()->create($data),
            'skill' => (function () use ($user, $data) {
                $skill = \App\Models\Skill::firstOrCreate(
                    ['label' => $data['label']],
                    ['code' => \Illuminate\Support\Str::slug($data['label']), 'type' => 'manual', 'slug' => \Illuminate\Support\Str::slug($data['label'])]
                );
                $user->skills()->syncWithoutDetaching([$skill->id]);
                return $skill;
            })(),
            'language' => (function () use ($user, $data) {
                $lang = \App\Models\Language::where('label', 'like', $data['label'])->first();
                if (!$lang) return null;
                $user->languages()->syncWithoutDetaching([$lang->id => ['level' => $data['level'] ?? 'Débutant']]);
                return ['id' => $lang->id, 'label' => $lang->label, 'level' => $data['level'] ?? 'Débutant'];
            })(),
            default => null
        };

        if (!$item) return response()->json(['error' => 'Creation failed'], 400);

        return response()->json(['success' => true, 'item' => $item]);
    }

    public function updateItem(Request $request, $type, $id)
    {
        $user = Auth::user();

        if ($type === 'user') {
            $validatedData = $request->validate([
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:255',
                'linkedin_url' => 'nullable|url|max:255',
                'github_url' => 'nullable|url|max:255',
                'portfolio_url' => 'nullable|url|max:255',
                'birth_date' => 'nullable',
                'links' => 'nullable|array',
                'headline' => 'nullable|string|max:255',
                'profile_text' => 'nullable|string',
                'aspirations' => 'nullable|string',
            ]);
            if (isset($validatedData['birth_date'])) $validatedData['birth_date'] = $this->sanitizeDate($validatedData['birth_date']);
            $user->update($validatedData);
            return response()->json(['success' => true, 'item' => $user->fresh()]);
        }

        $item = match ($type) {
            'experience' => $user->experiences()->find($id),
            'education' => $user->educations()->find($id),
            'project' => $user->projects()->find($id),
            'interest' => $user->interests()->find($id),
            'certification' => $user->certifications()->find($id),
            'volunteer' => $user->volunteerExperiences()->find($id),
            'fact' => $user->facts()->find($id),
            'skill' => $user->skills()->find($id),
            'language' => $user->languages()->find($id),
            default => null
        };

        if (!$item) return response()->json(['error' => 'Item not found'], 404);

        $rules = match ($type) {
            'experience' => [
                'company' => 'required|string|max:255',
                'company_logo' => 'nullable|string|max:255',
                'title' => 'required|string|max:255',
                'employment_type' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'location' => 'nullable|string|max:255',
                'start_date' => 'nullable',
                'end_date' => 'nullable',
                'is_current' => 'nullable|boolean',
            ],
            'education' => [
                'school' => 'required|string|max:255',
                'degree' => 'nullable|string|max:255',
                'field' => 'nullable|string|max:255',
                'start_date' => 'nullable',
                'graduation_year' => 'nullable',
                'grade' => 'nullable|string|max:255',
                'description' => 'nullable|string',
            ],
            'project' => [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'url' => 'nullable|string|max:255',
                'start_date' => 'nullable',
                'end_date' => 'nullable',
                'is_ongoing' => 'nullable|boolean',
            ],
            'interest' => [
                'name' => 'required|string|max:255',
            ],
            'certification' => [
                'name' => 'required|string|max:255',
                'issuing_organization' => 'nullable|string|max:255',
                'issue_date' => 'nullable',
                'expiration_date' => 'nullable',
                'credential_id' => 'nullable|string|max:255',
                'credential_url' => 'nullable|string|max:255',
            ],
            'volunteer' => [
                'organization' => 'required|string|max:255',
                'role' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'nullable',
                'end_date' => 'nullable',
            ],
            'fact' => [
                'content' => 'required|string|max:10000',
                'category' => 'nullable|string|max:255',
                'experience_id' => 'nullable|exists:experiences,id',
            ],
            'skill' => [
                'label' => 'required|string|max:255',
            ],
            'language' => [
                'label' => 'required|string|max:255',
                'level' => 'nullable|string|max:255',
            ],
            default => []
        };

        $data = $request->validate($rules);
        $data['status'] = 'validated'; // Manual edit validates the item

        // Sanitize dates if present
        if (isset($data['start_date'])) $data['start_date'] = $this->sanitizeDate($data['start_date']);
        if (isset($data['end_date'])) $data['end_date'] = $this->sanitizeDate($data['end_date']);
        if (isset($data['issue_date'])) $data['issue_date'] = $this->sanitizeDate($data['issue_date']);
        if (isset($data['expiration_date'])) $data['expiration_date'] = $this->sanitizeDate($data['expiration_date']);

        if ($type === 'skill') {
            if (isset($data['label']) && $item->label !== $data['label']) {
                $user->skills()->detach($id);
                $newSkill = \App\Models\Skill::firstOrCreate(
                    ['label' => $data['label']],
                    ['code' => \Illuminate\Support\Str::slug($data['label']), 'type' => 'manual', 'slug' => \Illuminate\Support\Str::slug($data['label'])]
                );
                $user->skills()->syncWithoutDetaching([$newSkill->id]);
                return response()->json(['success' => true, 'item' => $newSkill]);
            }
            return response()->json(['success' => true, 'item' => $item]);
        }

        if ($type === 'language') {
            $user->languages()->updateExistingPivot($id, ['level' => $data['level'] ?? $item->pivot->level]);
            return response()->json(['success' => true, 'item' => ['id' => $item->id, 'label' => $item->label, 'level' => $data['level'] ?? $item->pivot->level]]);
        }

        $item->update($data);

        return response()->json(['success' => true, 'item' => $item]);
    }

    public function deleteFact(UserFact $fact)
    {
        if ($fact->user_id !== Auth::id()) abort(403);
        $fact->delete();
        return $this->getUpdatedDataResponse(Auth::user(), session('profile_builder_session'), "Fait supprimé.");
    }

    public function acceptExperience($id)
    {
        return $this->acceptItem('experience', $id);
    }

    public function acceptEducation($id)
    {
        return $this->acceptItem('education', $id);
    }

    public function acceptItem($type, $id)
    {
        $user = auth()->user();
        $model = match($type) {
            'project' => $user->projects(),
            'certification' => $user->certifications(),
            'interest' => $user->interests(),
            'volunteer' => $user->volunteerExperiences(),
            'experience' => $user->experiences(),
            'education' => $user->educations(),
            default => null
        };

        if (!$model) return response()->json(['error' => 'Invalid type'], 400);
        
        $item = $model->findOrFail($id);

        if ($item->proposed_action === 'update' && $item->proposed_data) {
            $item->update(array_merge($item->proposed_data, [
                'proposed_data' => null,
                'proposed_action' => null,
                'status' => 'validated'
            ]));
        } elseif ($item->proposed_action === 'delete') {
            $item->delete();
        } else {
            $item->update([
                'status' => 'validated',
                'proposed_action' => null,
                'proposed_data' => null
            ]);
        }
        
        return $this->getUpdatedDataResponse($user, session('profile_builder_session'), "Élément validé.");
    }

    public function rejectItem($type, $id)
    {
        $user = auth()->user();
        $model = match($type) {
            'project' => $user->projects(),
            'certification' => $user->certifications(),
            'interest' => $user->interests(),
            'volunteer' => $user->volunteerExperiences(),
            'experience' => $user->experiences(),
            'education' => $user->educations(),
            default => null
        };

        if (!$model) return response()->json(['error' => 'Invalid type'], 400);
        
        $item = $model->findOrFail($id);

        if ($item->proposed_action === 'add') {
            $item->delete();
        } else {
            $item->update([
                'proposed_data' => null,
                'proposed_action' => null
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function deleteItem($type, $id)
    {
        $user = auth()->user();
        $model = match($type) {
            'project' => $user->projects(),
            'certification' => $user->certifications(),
            'interest' => $user->interests(),
            'volunteer' => $user->volunteerExperiences(),
            'experience' => $user->experiences(),
            'education' => $user->educations(),
            'skill' => $user->skills(),
            'fact' => $user->facts(),
            'language' => $user->languages(),
            default => null
        };

        if (!$model) return response()->json(['error' => 'Invalid type'], 400);
        
        $item = $model->findOrFail($id);
        
        if ($type === 'skill') {
            $user->skills()->detach($id);
        } elseif ($type === 'language') {
            $user->languages()->detach($id);
        } else {
            $item->delete();
        }
        
        return response()->json(['success' => true]);
    }

    protected function autoValidatePendingChanges($user)
    {
        // 1. Facts
        $user->facts()->whereNotNull('proposed_action')->get()->each(function($fact) {
            if ($fact->proposed_action === 'add') {
                $fact->update(['status' => 'validated', 'proposed_action' => null]);
            } elseif ($fact->proposed_action === 'update') {
                $fact->update([
                    'content' => $fact->proposed_content ?? $fact->content,
                    'category' => $fact->proposed_category ?? $fact->category,
                    'proposed_content' => null,
                    'proposed_category' => null,
                    'proposed_action' => null,
                    'status' => 'validated'
                ]);
            } elseif ($fact->proposed_action === 'delete') {
                $fact->delete();
            }
        });

        // 2. Other structured items
        $types = [
            'experiences' => $user->experiences(),
            'educations' => $user->educations(),
            'projects' => $user->projects(),
            'certifications' => $user->certifications(),
            'volunteer_experiences' => $user->volunteerExperiences(),
            'interests' => $user->interests()
        ];

        foreach ($types as $query) {
            $query->whereNotNull('proposed_action')->get()->each(function($item) {
                if ($item->proposed_action === 'update' && $item->proposed_data) {
                    $item->update(array_merge($item->proposed_data, [
                        'proposed_data' => null,
                        'proposed_action' => null,
                        'status' => 'validated'
                    ]));
                } elseif ($item->proposed_action === 'delete') {
                    $item->delete();
                } else {
                    $item->update([
                        'status' => 'validated',
                        'proposed_action' => null,
                        'proposed_data' => null
                    ]);
                }
            });
        }
    }
}
