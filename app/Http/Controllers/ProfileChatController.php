<?php

namespace App\Http\Controllers;

use App\Models\UserFact;
use App\Models\ProfileMessage;
use App\Models\Experience;
use App\Models\Education;
use App\Services\AIProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
            $openingMessage = $this->aiService->generateOpeningMessage($user);
            $user->profileMessages()->create([
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'role' => 'assistant',
                'content' => $openingMessage
            ]);
            // Re-fetch messages
            $messages = $user->profileMessages()->where('session_id', $sessionId)->orderBy('created_at', 'asc')->get();
        }

        $facts = $user->facts()
            ->with('skills')
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
            'categories' => $categoryCounts,
            'journey' => [
                'experiences' => $user->experiences()->count(),
                'educations' => $user->educations()->count(),
            ]
        ];

        $projects = $user->projects()->orderBy('updated_at', 'desc')->get();
        $certifications = $user->certifications()->orderBy('updated_at', 'desc')->get();
        $interests = $user->interests()->orderBy('updated_at', 'desc')->get();
        $volunteer_experiences = $user->volunteerExperiences()->orderBy('updated_at', 'desc')->get();
        $all_experiences = $user->experiences()->where('status', '!=', 'draft')->orderBy('start_date', 'desc')->get();
        $all_educations = $user->educations()->where('status', '!=', 'draft')->orderBy('graduation_year', 'desc')->get();

        return view('profile.builder', compact(
            'messages', 'facts', 'activeSessions', 'archivedSessions', 'sessionId', 'stats',
            'projects', 'certifications', 'interests', 'volunteer_experiences',
            'all_experiences', 'all_educations'
        ));
    }

    public function resetSession()
    {
        $newSessionId = uniqid();
        session(['profile_builder_session' => $newSessionId]);
        return redirect()->route('profile.builder');
    }

    public function sendMessage(Request $request)
    {
        $request->validate(['message' => 'required|string']);
        $user = Auth::user();
        $sessionId = session('profile_builder_session', uniqid());

        $session = \App\Models\ProfileSession::firstOrCreate(
            ['id' => $sessionId],
            ['user_id' => $user->id, 'title' => substr($request->message, 0, 50) . '...']
        );

        $user->profileMessages()->create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'role' => 'user',
            'content' => $request->message
        ]);

        if ($session->messages()->count() === 1) {
            $session->update(['title' => substr($request->message, 0, 50) . '...']);
        }
        
        $session->touch();

        $history = $user->profileMessages()
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->sortBy('created_at')
            ->values()
            ->map(fn($m) => ['role' => $m->role, 'content' => $m->content])
            ->toArray();

        $aiResponse = $this->aiService->chat($user, $history);
        Log::debug('AI RESPONSE DATA:', ['data' => $aiResponse]);

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

        // 5. Traiter les faits suggérés
        foreach ($aiResponse['facts'] ?? [] as $factData) {
            $rawId = $factData['id'] ?? null;
            $cleanId = $rawId ? preg_replace('/[^0-9]/', '', $rawId) : null;
            $action = $factData['action'] ?? ($cleanId ? 'update' : 'add');

            if ($action === 'delete' && $cleanId) {
                $fact = UserFact::find($cleanId);
                if ($fact && $fact->user_id === $user->id) {
                    $fact->update(['proposed_action' => 'delete']);
                }
            } elseif ($action === 'add' || !$cleanId) {
                $user->facts()->create([
                    'session_id' => $sessionId,
                    'content' => $factData['content'],
                    'category' => $factData['category'] ?? 'VALEURS',
                    'status' => 'validated',
                    'confidence_score' => 1.0
                ]);
            } elseif ($cleanId) {
                $fact = UserFact::find($cleanId);
                if ($fact && $fact->user_id === $user->id) {
                    $newContent = $factData['content'] ?? $fact->content;
                    $newCategory = $factData['category'] ?? $fact->category;

                    // Sécurité : ignorer si strictement identique à l'existant
                    if ($fact->content === $newContent && $fact->category === $newCategory) {
                        Log::debug("Fact {$cleanId} ignored: no changes detected.");
                        continue;
                    }

                    $fact->update([
                        'proposed_content' => $newContent,
                        'proposed_category' => $newCategory,
                        'proposed_action' => 'update'
                    ]);
                    Log::debug("Fact {$cleanId} updated with proposal.");
                } else {
                    // Fallback : si l'IA invente un ID ou se trompe, on traite comme un ajout
                    // pour éviter de perdre l'information.
                    $user->facts()->create([
                        'session_id' => $sessionId,
                        'content' => $factData['content'],
                        'category' => $factData['category'] ?? 'VALEURS',
                        'status' => 'validated',
                        'confidence_score' => 1.0
                    ]);
                    Log::debug("Fact ID {$cleanId} not found, falling back to 'add'.");
                }
            }
        }

        // 6. Traiter les expériences suggérées
        foreach ($aiResponse['experiences'] ?? [] as $expData) {
            if (($expData['action'] ?? 'add') === 'add') {
                $user->experiences()->create([
                    'company' => $expData['company'] ?? '?',
                    'company_logo' => $expData['company_logo'] ?? null,
                    'title' => $expData['title'] ?? '?',
                    'employment_type' => $expData['employment_type'] ?? null,
                    'description' => $expData['description'] ?? null,
                    'start_date' => $this->sanitizeDate($expData['start_date'] ?? null, now()->format('Y-m-d')),
                    'end_date' => $this->sanitizeDate($expData['end_date'] ?? null),
                    'is_current' => $expData['is_current'] ?? false,
                    'status' => 'draft',
                    'proposed_action' => 'add'
                ]);
            }
        }

        // 7. Traiter les formations suggérées
        foreach ($aiResponse['educations'] ?? [] as $eduData) {
            if (($eduData['action'] ?? 'add') === 'add') {
                $user->educations()->create([
                    'school' => $eduData['school'] ?? '?',
                    'degree' => $eduData['degree'] ?? '?',
                    'field' => $eduData['field'] ?? null,
                    'start_date' => $this->sanitizeDate($eduData['start_date'] ?? null),
                    'graduation_year' => $this->sanitizeYear($eduData['graduation_year'] ?? null),
                    'grade' => $eduData['grade'] ?? null,
                    'status' => 'draft',
                    'proposed_action' => 'add'
                ]);
            }
        }

        // 8. Traiter les projets suggérés
        foreach ($aiResponse['projects'] ?? [] as $projData) {
            if (($projData['action'] ?? 'add') === 'add') {
                $user->projects()->create([
                    'name' => $projData['name'] ?? '?',
                    'description' => $projData['description'] ?? null,
                    'url' => $projData['url'] ?? null,
                    'start_date' => $this->sanitizeDate($projData['start_date'] ?? null),
                    'end_date' => $this->sanitizeDate($projData['end_date'] ?? null),
                    'is_ongoing' => $projData['is_ongoing'] ?? false,
                ]);
            }
        }

        // 9. Certifications
        foreach ($aiResponse['certifications'] ?? [] as $certData) {
            if (($certData['action'] ?? 'add') === 'add') {
                $user->certifications()->create([
                    'name' => $certData['name'] ?? '?',
                    'issuing_organization' => $certData['issuing_organization'] ?? '?',
                    'issue_date' => $this->sanitizeDate($certData['issue_date'] ?? null),
                    'expiration_date' => $this->sanitizeDate($certData['expiration_date'] ?? null),
                    'credential_id' => $certData['credential_id'] ?? null,
                    'credential_url' => $certData['credential_url'] ?? null,
                ]);
            }
        }

        // 10. Bénévolat
        foreach ($aiResponse['volunteer_experiences'] ?? [] as $volData) {
            if (($volData['action'] ?? 'add') === 'add') {
                $user->volunteerExperiences()->create([
                    'organization' => $volData['organization'] ?? '?',
                    'role' => $volData['role'] ?? '?',
                    'description' => $volData['description'] ?? null,
                    'start_date' => $this->sanitizeDate($volData['start_date'] ?? null),
                    'end_date' => $this->sanitizeDate($volData['end_date'] ?? null),
                ]);
            }
        }

        // 11. Intérêts
        foreach ($aiResponse['interests'] ?? [] as $intData) {
            if (($intData['action'] ?? 'add') === 'add') {
                $user->interests()->firstOrCreate(['name' => $intData['name']]);
            }
        }

        // 12. Mises à jour utilisateur directes (Contact/Info)
        if (isset($aiResponse['user_updates'])) {
            $user->update(array_filter($aiResponse['user_updates']));
        }

        return response()->json([
            'reply' => $aiResponse['reply'],
            'facts' => $user->facts()
                ->with('skills')
                ->orderByRaw('CASE WHEN proposed_action IS NOT NULL THEN 0 ELSE 1 END')
                ->orderBy('updated_at', 'desc')
                ->get(),
            'projects' => $user->projects()->orderBy('updated_at', 'desc')->get(),
            'certifications' => $user->certifications()->orderBy('updated_at', 'desc')->get(),
            'interests' => $user->interests()->orderBy('updated_at', 'desc')->get(),
            'volunteer_experiences' => $user->volunteerExperiences()->orderBy('updated_at', 'desc')->get(),
            'all_experiences' => $user->experiences()->where('status', '!=', 'draft')->orderBy('start_date', 'desc')->get(),
            'all_educations' => $user->educations()->where('status', '!=', 'draft')->orderBy('graduation_year', 'desc')->get(),
            'activeSessions' => $user->profileSessions()->where('is_archived', false)->get(),
            'archivedSessions' => $user->profileSessions()->where('is_archived', true)->get()
        ]);
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

    public function syncSkills(Request $request, \App\Services\ProfileMappingService $mappingService)
    {
        return response()->json($mappingService->mapUserFacts(Auth::user()));
    }

    public function updateFact(Request $request, UserFact $fact)
    {
        if ($fact->user_id !== Auth::id()) abort(403);
        $fact->update(['content' => $request->content, 'status' => 'validated']);
        return response()->json(['success' => true]);
    }

    public function validateFact(UserFact $fact)
    {
        if ($fact->user_id !== Auth::id()) abort(403);
        $fact->update(['status' => 'validated', 'proposed_action' => null]);
        return response()->json(['success' => true]);
    }

    public function acceptProposal(UserFact $fact)
    {
        if ($fact->user_id !== Auth::id()) abort(403);
        if ($fact->proposed_action === 'delete') {
            $fact->delete();
            return response()->json(['success' => true, 'deleted' => true]);
        }
        $fact->update([
            'content' => $fact->proposed_content ?? $fact->content,
            'category' => $fact->proposed_category ?? $fact->category,
            'proposed_action' => null,
            'status' => 'validated'
        ]);
        return response()->json(['success' => true, 'fact' => $fact]);
    }

    public function rejectProposal(UserFact $fact)
    {
        if ($fact->user_id !== Auth::id()) abort(403);
        $fact->update(['proposed_action' => null]);
        return response()->json(['success' => true]);
    }

    public function deleteFact(UserFact $fact)
    {
        if ($fact->user_id !== Auth::id()) abort(403);
        $fact->delete();
        return response()->json(['success' => true]);
    }
}
