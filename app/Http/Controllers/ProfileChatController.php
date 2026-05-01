<?php

namespace App\Http\Controllers;

use App\Models\UserFact;
use App\Models\ProfileMessage;
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

        // 1. On récupère les sessions actives et archivées séparément pour la sidebar
        $activeSessions = $user->profileSessions()
            ->where('is_archived', false)
            ->get();
            
        $archivedSessions = $user->profileSessions()
            ->where('is_archived', true)
            ->get();

        // 2. Déterminer quelle session afficher
        // Priorité 1 : Le paramètre ?session dans l'URL
        // Priorité 2 : La session stockée en PHP session
        // Priorité 3 : La session la plus récente de la DB
        $sessionId = $request->query('session') ?? session('profile_builder_session');

        if (!$sessionId) {
            $sessionId = $sessions->first()?->session_id ?? uniqid();
        }
        
        // On mémorise la session active
        session(['profile_builder_session' => $sessionId]);

        $messages = $user->profileMessages()->where('session_id', $sessionId)->orderBy('created_at', 'asc')->get();
        $facts = $user->facts()->with('skills')->orderBy('created_at', 'desc')->get();

        return view('profile.builder', compact('messages', 'facts', 'activeSessions', 'archivedSessions', 'sessionId'));
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

        // 1. Assurer l'existence de la session et sauvegarder le message
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

        // Mettre à jour le titre si c'est le premier message et qu'il était par défaut
        if ($session->messages()->count() === 1) {
            $session->update(['title' => substr($request->message, 0, 50) . '...']);
        }
        
        $session->touch(); // Pour mettre à jour updated_at et faire remonter la session

        // 2. Préparer l'historique pour l'IA (uniquement de la session actuelle)
        // On prend les 20 derniers messages, puis on les remet dans l'ordre chronologique
        $history = $user->profileMessages()
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->sortBy('created_at')
            ->values()
            ->map(fn($m) => ['role' => $m->role, 'content' => $m->content])
            ->toArray();

        // 3. Appeler l'IA
        $aiResponse = $this->aiService->chat($user, $history);

        if (!$aiResponse || !isset($aiResponse['reply'])) {
            return response()->json([
                'reply' => "Désolé, j'ai eu un petit souci pour traiter votre message. Pouvez-vous reformuler ?",
                'facts' => $user->facts()->orderBy('created_at', 'desc')->get()
            ]);
        }

        // 4. Sauvegarder la réponse de l'IA
        $user->profileMessages()->create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'role' => 'assistant',
            'content' => $aiResponse['reply']
        ]);

        // 5. Traiter les faits suggérés
        if (isset($aiResponse['facts']) && is_array($aiResponse['facts'])) {
            foreach ($aiResponse['facts'] as $factData) {
                // Nettoyage de l'ID (on extrait uniquement les chiffres au cas où l'IA met des crochets)
                $rawId = $factData['id'] ?? null;
                $cleanId = $rawId ? preg_replace('/[^0-9]/', '', $rawId) : null;
                
                $action = $factData['action'] ?? ($cleanId ? 'update' : 'add');

                if ($action === 'delete' && $cleanId) {
                    $fact = UserFact::find($cleanId);
                    if ($fact && $fact->user_id === $user->id) {
                        Log::info("AI Action: PROPOSE DELETE fact {$cleanId}");
                        $fact->update([
                            'session_id' => $sessionId,
                            'proposed_action' => 'delete'
                        ]);
                    }
                } elseif ($action === 'add' || !$cleanId) {
                    // Nouvel ajout (soit forcé par 'add', soit pas d'ID présent)
                    Log::info("AI Action: ADD new fact");
                    $user->facts()->create([
                        'session_id' => $sessionId,
                        'content' => $factData['content'],
                        'category' => $factData['category'] ?? 'CONTEXT',
                        'status' => 'draft',
                        'confidence_score' => 1.0
                    ]);
                } elseif ($cleanId) {
                    // Mise à jour (si l'ID existe et appartient à l'utilisateur)
                    $fact = UserFact::find($cleanId);
                    if ($fact && $fact->user_id === $user->id) {
                        $newContent = trim($factData['content'] ?? $fact->content);
                        $newCategory = $factData['category'] ?? $fact->category;

                        // On ne crée une proposition que si c'est réellement différent
                        if ($fact->content === $newContent && $fact->category === $newCategory) {
                            Log::info("AI Action: UPDATE fact {$cleanId} IGNORED (identical)");
                            continue;
                        }

                        Log::info("AI Action: PROPOSE UPDATE fact {$cleanId}");
                        $fact->update([
                            'session_id' => $sessionId,
                            'proposed_content' => $newContent,
                            'proposed_category' => $newCategory,
                            'proposed_action' => 'update'
                        ]);
                    } else {
                        // Si l'ID n'existe pas, on considère que c'est un ajout (l'IA invente parfois des IDs)
                        Log::info("AI Action: ADD fact (ID {$cleanId} not found, fallback to creation)");
                        $user->facts()->create([
                            'session_id' => $sessionId,
                            'content' => $factData['content'],
                            'category' => $factData['category'] ?? 'CONTEXT',
                            'status' => 'draft',
                            'confidence_score' => 1.0
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'reply' => $aiResponse['reply'],
            'facts' => $user->facts()->with('skills')->orderBy('created_at', 'desc')->get(),
            'activeSessions' => $user->profileSessions()->where('is_archived', false)->get(),
            'archivedSessions' => $user->profileSessions()->where('is_archived', true)->get(),
            'debug' => [
                'history' => $history,
                'session_id' => $sessionId
            ]
        ]);
    }

    public function toggleArchive(Request $request, $sessionId)
    {
        $session = \App\Models\ProfileSession::where('id', $sessionId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $session->update([
            'is_archived' => !$session->is_archived
        ]);

        return response()->json(['success' => true]);
    }

    public function syncSkills(Request $request, \App\Services\ProfileMappingService $mappingService)
    {
        $result = $mappingService->mapUserFacts(Auth::user());
        
        return response()->json($result);
    }

    public function updateFact(Request $request, UserFact $fact)
    {
        if ($fact->user_id !== Auth::id()) abort(403);
        $request->validate(['content' => 'required|string']);
        
        $fact->update([
            'content' => $request->content,
            'status' => 'validated'
        ]);

        return response()->json(['success' => true]);
    }

    public function validateFact(UserFact $fact)
    {
        if ($fact->user_id !== Auth::id()) abort(403);
        $fact->update([
            'status' => 'validated',
            'proposed_content' => null, 
            'proposed_category' => null,
            'proposed_action' => null
        ]);
        return response()->json(['success' => true]);
    }

    public function acceptProposal(UserFact $fact)
    {
        if ($fact->user_id !== Auth::id()) abort(403);
        
        if ($fact->proposed_action === 'delete') {
            $fact->delete();
            return response()->json(['success' => true, 'deleted' => true]);
        }

        if (!$fact->proposed_content) return response()->json(['success' => false]);

        $fact->update([
            'content' => $fact->proposed_content,
            'category' => $fact->proposed_category ?? $fact->category,
            'proposed_content' => null,
            'proposed_category' => null,
            'proposed_action' => null,
            'status' => 'validated'
        ]);

        return response()->json(['success' => true, 'fact' => $fact]);
    }

    public function rejectProposal(UserFact $fact)
    {
        if ($fact->user_id !== Auth::id()) abort(403);
        $fact->update([
            'proposed_content' => null,
            'proposed_category' => null,
            'proposed_action' => null
        ]);

        return response()->json(['success' => true]);
    }

    public function deleteFact(UserFact $fact)
    {
        if ($fact->user_id !== Auth::id()) abort(403);
        $fact->delete();
        return response()->json(['success' => true]);
    }
}
