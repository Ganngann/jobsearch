<?php

namespace App\Http\Controllers;

use App\Models\UserFact;
use App\Models\ProfileMessage;
use App\Services\AIProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        
        // 1. On récupère la liste des sessions pour la sidebar
        $sessions = $user->profileMessages()
            ->select('session_id', \DB::raw('min(created_at) as created_at'), \DB::raw('min(content) as title'))
            ->groupBy('session_id')
            ->orderBy('created_at', 'desc')
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
        $facts = $user->facts()->orderBy('created_at', 'desc')->get();

        return view('profile.builder', compact('messages', 'facts', 'sessions', 'sessionId'));
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

        // 1. Sauvegarder le message de l'utilisateur
        $user->profileMessages()->create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'role' => 'user',
            'content' => $request->message
        ]);

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
                    Log::info("AI Action: DELETE fact {$cleanId}");
                    UserFact::where('id', $cleanId)->where('user_id', $user->id)->delete();
                } elseif ($cleanId) {
                    // Mise à jour ou Fusion
                    $fact = UserFact::find($cleanId);
                    if ($fact && $fact->user_id === $user->id) {
                        Log::info("AI Action: UPDATE fact {$cleanId}");
                        $fact->update([
                            'content' => $factData['content'] ?? $fact->content,
                            'category' => $factData['category'] ?? $fact->category,
                        ]);
                    }
                } else {
                    // Nouvel ajout
                    Log::info("AI Action: ADD new fact");
                    $user->facts()->create([
                        'content' => $factData['content'],
                        'category' => $factData['category'] ?? 'CONTEXT',
                        'status' => 'draft',
                        'confidence_score' => 1.0
                    ]);
                }
            }
        }

        return response()->json([
            'reply' => $aiResponse['reply'],
            'facts' => $user->facts()->orderBy('created_at', 'desc')->get(),
            'debug' => [
                'history' => $history,
                'session_id' => $sessionId
            ]
        ]);
    }

    public function updateFact(Request $request, UserFact $fact)
    {
        if ($fact->user_id !== Auth::id()) abort(403);
        $request->validate(['content' => 'required|string']);
        
        $fact->update([
            'content' => $request->content,
            'status' => 'validated' // On valide automatiquement si l'utilisateur modifie manuellement
        ]);

        return response()->json(['success' => true]);
    }

    public function validateFact(UserFact $fact)
    {
        if ($fact->user_id !== Auth::id()) abort(403);
        $fact->update(['status' => 'validated']);
        return response()->json(['success' => true]);
    }

    public function deleteFact(UserFact $fact)
    {
        if ($fact->user_id !== Auth::id()) abort(403);
        $fact->delete();
        return response()->json(['success' => true]);
    }
}
