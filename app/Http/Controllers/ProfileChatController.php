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
        $all_experiences = $user->experiences()->orderBy('start_date', 'desc')->get();
        $all_educations = $user->educations()->orderBy('graduation_year', 'desc')->get();

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

        // AUTO-VALIDATION : Désactivé pour éviter les vagues de doublons et laisser le contrôle à l'utilisateur
        // $this->autoValidatePendingChanges($user);

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

        // 5. Traiter les faits suggérés (avec consolidation pour éviter les écrasements)
        $factUpdates = [];
        foreach ($aiResponse['facts'] ?? [] as $factData) {
            $rawId = $factData['id'] ?? null;
            $cleanId = $rawId ? preg_replace('/[^0-9]/', '', $rawId) : null;
            $action = $factData['action'] ?? ($cleanId ? 'update' : 'add');

            if ($action === 'delete' && $cleanId) {
                $fact = $user->facts()->where('local_id', $cleanId)->first();
                if ($fact) $fact->update(['proposed_action' => 'delete']);
            } elseif ($action === 'update' && $cleanId) {
                if (!isset($factUpdates[$cleanId])) {
                    $factUpdates[$cleanId] = $factData;
                } else {
                    // Consolidation : si l'IA envoie plusieurs blocs pour le même ID, on les fusionne
                    $factUpdates[$cleanId]['content'] .= " " . $factData['content'];
                }
            } else {
                // Ajout
                $user->facts()->create([
                    'session_id' => $sessionId,
                    'content' => $factData['content'],
                    'category' => $factData['category'] ?? 'VALEURS',
                    'status' => 'draft',
                    'proposed_action' => 'add',
                    'confidence_score' => 1.0
                ]);
            }
        }

        // Appliquer les mises à jour consolidées
        foreach ($factUpdates as $cleanId => $factData) {
            $fact = $user->facts()->where('local_id', $cleanId)->first();
            if ($fact) {
                $newContent = trim($factData['content']);
                $newCategory = $factData['category'] ?? $fact->category;

                if ($fact->content !== $newContent || $fact->category !== $newCategory) {
                    $fact->update([
                        'proposed_content' => $newContent,
                        'proposed_category' => $newCategory,
                        'proposed_action' => 'update'
                    ]);
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
            } elseif ($expData['action'] === 'update' && isset($expData['id'])) {
                $exp = $user->experiences()->find($expData['id']);
                if ($exp) {
                    $newData = array_filter([
                        'company' => $expData['company'] ?? null,
                        'title' => $expData['title'] ?? null,
                        'description' => $expData['description'] ?? null,
                        'employment_type' => $expData['employment_type'] ?? null,
                        'location' => $expData['location'] ?? null,
                        'start_date' => isset($expData['start_date']) ? $this->sanitizeDate($expData['start_date']) : null,
                        'end_date' => isset($expData['end_date']) ? $this->sanitizeDate($expData['end_date']) : null,
                        'is_current' => $expData['is_current'] ?? null,
                    ]);
                    
                    if (!empty($newData)) {
                        $exp->update([
                            'proposed_data' => $newData,
                            'proposed_action' => 'update'
                        ]);
                    }
                }
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
                    'description' => $eduData['description'] ?? null,
                    'status' => 'draft',
                    'proposed_action' => 'add'
                ]);
            } elseif ($eduData['action'] === 'update' && isset($eduData['id'])) {
                $edu = $user->educations()->find($eduData['id']);
                if ($edu) {
                    $newData = array_filter([
                        'school' => $eduData['school'] ?? null,
                        'degree' => $eduData['degree'] ?? null,
                        'field' => $eduData['field'] ?? null,
                        'description' => $eduData['description'] ?? null,
                        'start_date' => isset($eduData['start_date']) ? $this->sanitizeDate($eduData['start_date']) : null,
                        'graduation_year' => $this->sanitizeYear($eduData['graduation_year'] ?? null),
                        'grade' => $eduData['grade'] ?? null,
                    ]);
                    
                    if (!empty($newData)) {
                        $edu->update([
                            'proposed_data' => $newData,
                            'proposed_action' => 'update'
                        ]);
                    }
                }
            }
        }

        // 8. Projets
        foreach ($aiResponse['projects'] ?? [] as $projData) {
            if (($projData['action'] ?? 'add') === 'add') {
                $user->projects()->create([
                    'name' => $projData['name'] ?? '?',
                    'description' => $projData['description'] ?? '',
                    'url' => $projData['url'] ?? null,
                    'start_date' => $this->sanitizeDate($projData['start_date'] ?? null),
                    'is_ongoing' => $projData['is_ongoing'] ?? false,
                    'status' => 'draft'
                ]);
            } elseif ($projData['action'] === 'update' && isset($projData['id'])) {
                $proj = $user->projects()->find($projData['id']);
                if ($proj) {
                    $newData = array_filter([
                        'name' => $projData['name'] ?? null,
                        'description' => $projData['description'] ?? null,
                        'url' => $projData['url'] ?? null,
                        'start_date' => isset($projData['start_date']) ? $this->sanitizeDate($projData['start_date']) : null,
                        'is_ongoing' => $projData['is_ongoing'] ?? null,
                    ]);
                    
                    if (!empty($newData)) {
                        $proj->update([
                            'proposed_data' => $newData,
                            'proposed_action' => 'update'
                        ]);
                    }
                }
            }
        }

        foreach ($aiResponse['certifications'] ?? [] as $certData) {
            if (($certData['action'] ?? 'add') === 'add') {
                $user->certifications()->create([
                    'name' => $certData['name'] ?? '?',
                    'issuing_organization' => $certData['issuing_organization'] ?? '?',
                    'issue_date' => $this->sanitizeDate($certData['issue_date'] ?? null),
                    'expiration_date' => $this->sanitizeDate($certData['expiration_date'] ?? null),
                    'credential_id' => $certData['credential_id'] ?? null,
                    'credential_url' => $certData['credential_url'] ?? null,
                    'status' => 'draft',
                    'proposed_action' => 'add'
                ]);
            } elseif ($certData['action'] === 'update' && isset($certData['id'])) {
                $cert = $user->certifications()->find($certData['id']);
                if ($cert) {
                    $newData = array_filter([
                        'name' => $certData['name'] ?? null,
                        'issuing_organization' => $certData['issuing_organization'] ?? null,
                        'issue_date' => isset($certData['issue_date']) ? $this->sanitizeDate($certData['issue_date']) : null,
                        'expiration_date' => isset($certData['expiration_date']) ? $this->sanitizeDate($certData['expiration_date']) : null,
                        'credential_id' => $certData['credential_id'] ?? null,
                        'credential_url' => $certData['credential_url'] ?? null,
                    ]);
                    if (!empty($newData)) {
                        $cert->update(['proposed_data' => $newData, 'proposed_action' => 'update']);
                    }
                }
            } elseif ($certData['action'] === 'delete' && isset($certData['id'])) {
                $cert = $user->certifications()->find($certData['id']);
                if ($cert) $cert->update(['proposed_action' => 'delete']);
            }
        }

        // 10. Bénévolat
        foreach ($aiResponse['volunteer_experiences'] ?? [] as $volData) {
            if (($volData['action'] ?? 'add') === 'add') {
                $user->volunteerExperiences()->create([
                    'organization' => $volData['organization'] ?? '?',
                    'role' => $volData['role'] ?? '?',
                    'description' => $volData['description'] ?? '',
                    'start_date' => $this->sanitizeDate($volData['start_date'] ?? null),
                    'end_date' => $this->sanitizeDate($volData['end_date'] ?? null),
                    'status' => 'draft',
                    'proposed_action' => 'add'
                ]);
            } elseif ($volData['action'] === 'update' && isset($volData['id'])) {
                $vol = $user->volunteerExperiences()->find($volData['id']);
                if ($vol) {
                    $newData = array_filter([
                        'organization' => $volData['organization'] ?? null,
                        'role' => $volData['role'] ?? null,
                        'description' => $volData['description'] ?? null,
                        'start_date' => isset($volData['start_date']) ? $this->sanitizeDate($volData['start_date']) : null,
                        'end_date' => isset($volData['end_date']) ? $this->sanitizeDate($volData['end_date']) : null,
                    ]);
                    if (!empty($newData)) {
                        $vol->update(['proposed_data' => $newData, 'proposed_action' => 'update']);
                    }
                }
            } elseif ($volData['action'] === 'delete' && isset($volData['id'])) {
                $vol = $user->volunteerExperiences()->find($volData['id']);
                if ($vol) $vol->update(['proposed_action' => 'delete']);
            }
        }

        // 11. Intérêts
        foreach ($aiResponse['interests'] ?? [] as $intData) {
            if (($intData['action'] ?? 'add') === 'add') {
                $user->interests()->firstOrCreate(
                    ['name' => $intData['name'], 'user_id' => $user->id],
                    ['status' => 'draft']
                );
            }
        }

        // 12. Mises à jour utilisateur directes (Contact/Info)
        if (isset($aiResponse['user_updates'])) {
            $updates = array_filter($aiResponse['user_updates']);
            
            // On récupère les liens existants
            $currentLinks = $user->links ?? [];
            $newLinksFound = false;

            // Si l'IA envoie les anciens champs, on les convertit en liens
            $mapped = [
                'github_url' => 'GitHub',
                'linkedin_url' => 'LinkedIn',
                'portfolio_url' => 'Portfolio'
            ];

            foreach ($mapped as $field => $label) {
                if (isset($updates[$field]) && !empty($updates[$field])) {
                    $currentLinks[] = ['label' => $label, 'url' => $updates[$field]];
                    $newLinksFound = true;
                }
            }

            // Si l'IA envoie déjà le format 'links'
            if (isset($updates['links']) && is_array($updates['links'])) {
                foreach($updates['links'] as $link) {
                    if (isset($link['url'])) {
                        $currentLinks[] = $link;
                        $newLinksFound = true;
                    }
                }
            }

            if ($newLinksFound) {
                // Déduplication par URL
                $uniqueLinks = [];
                foreach($currentLinks as $link) {
                    if (isset($link['url'])) {
                        $uniqueLinks[$link['url']] = $link;
                    }
                }
                $updates['links'] = array_values($uniqueLinks);
            }

            if (isset($updates['birth_date'])) {
                $updates['birth_date'] = $this->sanitizeDate($updates['birth_date']);
            }
            
            $user->update($updates);
        }

        $user = $user->fresh();

        return response()->json([
            'reply' => $aiResponse['reply'],
            'user' => $user,
            'facts' => $user->facts()
                ->with('skills')
                ->orderByRaw('CASE WHEN proposed_action IS NOT NULL THEN 0 ELSE 1 END')
                ->orderBy('updated_at', 'desc')
                ->get(),
            'projects' => $user->projects()->orderBy('updated_at', 'desc')->get(),
            'certifications' => $user->certifications()->orderBy('updated_at', 'desc')->get(),
            'interests' => $user->interests()->orderBy('updated_at', 'desc')->get(),
            'volunteer_experiences' => $user->volunteerExperiences()->orderBy('updated_at', 'desc')->get(),
            'all_experiences' => $user->experiences()->orderBy('start_date', 'desc')->get(),
            'all_educations' => $user->educations()->orderBy('graduation_year', 'desc')->get(),
            'skills' => $user->skills()->get(),
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

        return response()->json(['success' => true]);
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

        return response()->json(['success' => true]);
    }

    public function storeItem(Request $request, $type)
    {
        $user = Auth::user();
        $data = $request->all();
        $data['status'] = 'validated'; // Manual addition is pre-validated
        
        // Sanitize dates if present
        if (isset($data['start_date'])) $data['start_date'] = $this->sanitizeDate($data['start_date']);
        if (isset($data['end_date'])) $data['end_date'] = $this->sanitizeDate($data['end_date']);
        if (isset($data['issue_date'])) $data['issue_date'] = $this->sanitizeDate($data['issue_date']);
        if (isset($data['expiration_date'])) $data['expiration_date'] = $this->sanitizeDate($data['expiration_date']);

        $item = match($type) {
            'experience' => $user->experiences()->create($data),
            'education' => $user->educations()->create($data),
            'project' => $user->projects()->create($data),
            'interest' => $user->interests()->create($data),
            'certification' => $user->certifications()->create($data),
            'volunteer' => $user->volunteerExperiences()->create($data),
            'fact' => $user->facts()->create($data),
            'skill' => (function() use ($user, $data) {
                if (empty($data['label'])) return null;
                $skill = \App\Models\Skill::firstOrCreate(
                    ['label' => $data['label']],
                    ['code' => \Illuminate\Support\Str::slug($data['label']), 'type' => 'manual', 'slug' => \Illuminate\Support\Str::slug($data['label'])]
                );
                $user->skills()->syncWithoutDetaching([$skill->id]);
                return $skill;
            })(),
            default => null
        };

        if (!$item) return response()->json(['error' => 'Invalid type or creation failed'], 400);

        return response()->json(['success' => true, 'item' => $item]);
    }

    public function updateItem(Request $request, $type, $id)
    {
        $user = Auth::user();
        
        if ($type === 'user') {
            $user->update($request->only(['name', 'email', 'phone', 'linkedin_url', 'github_url', 'portfolio_url', 'birth_date', 'links', 'headline', 'profile_text', 'aspirations']));
            return response()->json(['success' => true, 'item' => $user->fresh()]);
        }

        $item = match($type) {
            'experience' => $user->experiences()->find($id),
            'education' => $user->educations()->find($id),
            'project' => $user->projects()->find($id),
            'interest' => $user->interests()->find($id),
            'certification' => $user->certifications()->find($id),
            'volunteer' => $user->volunteerExperiences()->find($id),
            'fact' => $user->facts()->find($id),
            'skill' => $user->skills()->find($id),
            default => null
        };

        if (!$item) return response()->json(['error' => 'Item not found'], 404);

        // Update all provided fields and set status to validated
        $data = $request->all();
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

        $item->update($data);

        return response()->json(['success' => true, 'item' => $item]);
    }

    public function deleteFact(UserFact $fact)
    {
        if ($fact->user_id !== Auth::id()) abort(403);
        $fact->delete();
        return response()->json(['success' => true]);
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
        
        return response()->json(['success' => true]);
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
            default => null
        };

        if (!$model) return response()->json(['error' => 'Invalid type'], 400);
        
        $item = $model->findOrFail($id);
        
        if ($type === 'skill') {
            $user->skills()->detach($id);
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
