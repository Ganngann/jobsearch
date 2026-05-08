<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        $users = User::withCount(['matches as ai_calls_count' => function($query) {
            $query->whereNotNull('analyzed_at');
        }])
        ->with(['aiLogs'])
        ->orderBy('last_seen_at', 'desc')
        ->paginate(50);

        // Statistiques globales détaillées
        $aiStats = \App\Models\AiLog::select(
            'model',
            'category',
            DB::raw('count(*) as count'),
            DB::raw('sum(tokens_in) as total_in'),
            DB::raw('sum(tokens_out) as total_out')
        )
        ->groupBy('model', 'category')
        ->get();

        $stats = [
            'total_users' => User::count(),
            'total_ai_calls' => \App\Models\AiLog::count(),
            'active_users_today' => User::whereDate('last_seen_at', today())->count(),
            'ai_details' => $aiStats,
            'total_tokens_in' => $aiStats->sum('total_in'),
            'total_tokens_out' => $aiStats->sum('total_out'),
        ];

        return view('admin.dashboard', compact('users', 'stats'));
    }

    public function toggleAdmin(User $user)
    {
        $user->update(['is_admin' => !$user->is_admin]);
        return back()->with('success', "Statut admin de {$user->name} mis à jour.");
    }

    public function updateLimit(Request $request, User $user)
    {
        $request->validate(['limit' => 'required|integer|min:0']);
        $user->update(['daily_ai_limit' => $request->limit]);
        return back()->with('success', "Limite IA de {$user->name} mise à jour.");
    }
}
