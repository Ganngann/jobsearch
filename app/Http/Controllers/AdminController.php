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
        $users = User::withCount(['aiLogs as ai_calls_count'])
        ->with(['aiLogs'])
        ->orderBy('last_seen_at', 'desc')
        ->paginate(50);

        // On récupère tous les modèles connus via les settings de limites
        $availableModels = \App\Models\Setting::where('key', 'like', 'limit_%')
            ->pluck('key')
            ->map(fn($k) => str_replace('limit_', '', $k))
            ->unique();

        // Calcul du coût par utilisateur (ventilé par modèle pour l'accordéon)
        $users->getCollection()->transform(function($user) use ($availableModels) {
            $actualLogs = $user->aiLogs->groupBy('model');
            $userModels = $actualLogs->keys();
            $allUserModels = $availableModels->concat($userModels)->unique();
            
            $user->ai_details = $allUserModels->map(function($model) use ($actualLogs) {
                $logs = $actualLogs->get($model) ?? collect();
                
                $rateIn = (float) \App\Models\Setting::get("rate_in_{$model}", 0.10) / 1000000;
                $rateOut = (float) \App\Models\Setting::get("rate_out_{$model}", 0.30) / 1000000;
                
                $totalIn = $logs->sum('tokens_in');
                $totalOut = $logs->sum('tokens_out');
                
                return (object)[
                    'model' => $model,
                    'category' => $logs->first()?->category ?? '-', // Pas de catégorie si pas de log
                    'count' => $logs->count(),
                    'total_in' => $totalIn,
                    'total_out' => $totalOut,
                    'cost' => ($totalIn * $rateIn) + ($totalOut * $rateOut)
                ];
            })->values();
            
            $user->total_cost = $user->ai_details->sum('cost');
            return $user;
        });

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

        $aiStats = $aiStats->map(function($stat) {
            $rateIn = (float) \App\Models\Setting::get("rate_in_{$stat->model}", 0.10) / 1000000;
            $rateOut = (float) \App\Models\Setting::get("rate_out_{$stat->model}", 0.30) / 1000000;
            $stat->cost = ($stat->total_in * $rateIn) + ($stat->total_out * $rateOut);
            return $stat;
        });

        $stats = [
            'total_users' => User::count(),
            'total_ai_calls' => \App\Models\AiLog::count(),
            'active_users_today' => User::whereDate('last_seen_at', today())->count(),
            'ai_details' => $aiStats,
            'total_tokens_in' => $aiStats->sum('total_in'),
            'total_tokens_out' => $aiStats->sum('total_out'),
            'total_cost' => $aiStats->sum('cost'),
            
            // Stats de Matching & Vecteurs
            'jobs_total' => \App\Models\JobOffer::count(),
            'jobs_vectorized' => \App\Models\JobOffer::whereNotNull('vector_embedding')->count(),
            'jobs_active_vectorized' => \App\Models\JobOffer::where('status', 'active')->whereNotNull('vector_embedding')->count(),
            'jobs_pending_vectorization' => \App\Models\JobOffer::where('status', 'active')->whereNull('vector_embedding')->count(),
            'matches_total' => \App\Models\UserMatch::count(),
            'matches_ai' => \App\Models\UserMatch::whereNotNull('ai_score')->count(),
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
        $request->validate([
            'limit' => 'required|integer|min:0',
            'model' => 'nullable|string'
        ]);

        if ($request->model) {
            $limits = $user->daily_ai_limits ?? [];
            $limits[$request->model] = (int)$request->limit;
            $user->update(['daily_ai_limits' => $limits]);
            return back()->with('success', "Limite IA pour {$request->model} mise à jour pour {$user->name}.");
        }

        $user->update(['daily_ai_limit' => $request->limit]);
        return back()->with('success', "Limite IA globale de {$user->name} mise à jour.");
    }

    public function clearAllMatches()
    {
        \Illuminate\Support\Facades\Log::emergency("!!! ADMIN: RESETTING TECHNICAL MATCHES !!!");
        
        try {
            // 1. On supprime les matches qui n'ont pas d'IA complétée
            $deleted = \Illuminate\Support\Facades\DB::table('user_matches')
                ->where(function($q) {
                    $q->whereNull('ai_status')->orWhere('ai_status', '!=', 'completed');
                })
                ->delete();
            
            // 2. Pour ceux qui ont une IA, on reset juste les scores techniques pour forcer le recalcul
            \Illuminate\Support\Facades\DB::table('user_matches')
                ->where('ai_status', 'completed')
                ->update([
                    'vector_score' => 0,
                    'pre_score' => 100,
                    'final_score' => \Illuminate\Support\Facades\DB::raw('ai_score') // On garde le score IA en maître
                ]);

            \Illuminate\Support\Facades\Log::info("ADMIN: Technical reset completed. Deleted: {$deleted}");
            
            return redirect()->route('admin.dashboard')->with('success', "Scores techniques réinitialisés ({$deleted} supprimés). Les analyses IA ont été préservées.");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("ADMIN: Failed to reset matches: " . $e->getMessage());
            return redirect()->route('admin.dashboard')->with('error', "Erreur : " . $e->getMessage());
        }
    }

    public function clearAiAnalyses()
    {
        \Illuminate\Support\Facades\Log::emergency("!!! ADMIN: PURGING ALL AI ANALYSES !!!");
        
        try {
            $count = UserMatch::whereNotNull('ai_score')->count();
            
            UserMatch::query()->update([
                'ai_score' => null,
                'ai_status' => 'pending',
                'ai_analysis_narrative' => null,
                'ai_recommendation' => null,
                'strengths' => null,
                'weaknesses' => null,
                'ai_raw_response' => null,
                'analyzed_at' => null,
            ]);

            // On recalcule le final_score sur la base du vecteur puisqu'il n'y a plus d'IA
            \Illuminate\Support\Facades\DB::table('user_matches')->update([
                'final_score' => \Illuminate\Support\Facades\DB::raw('ROUND(vector_score * (pre_score / 100))')
            ]);
            
            return redirect()->route('admin.dashboard')->with('success', "Toutes les analyses IA ({$count}) ont été purgées.");
        } catch (\Exception $e) {
            return redirect()->route('admin.dashboard')->with('error', "Erreur : " . $e->getMessage());
        }
    }

    /**
     * Affiche l'état de la file d'attente des tâches.
     */
    public function queueMonitor()
    {
        $pendingJobs = \Illuminate\Support\Facades\DB::connection('queue')->table('jobs')
            ->orderBy('id', 'desc')
            ->paginate(50, ['*'], 'pending_page');

        $failedJobs = \Illuminate\Support\Facades\DB::connection('queue')->table('failed_jobs')
            ->orderBy('id', 'desc')
            ->paginate(10, ['*'], 'failed_page');

        $pendingCount = \Illuminate\Support\Facades\DB::connection('queue')->table('jobs')->count();
        $failedCount = \Illuminate\Support\Facades\DB::connection('queue')->table('failed_jobs')->count();
        
        return view('admin.queue', compact('pendingJobs', 'failedJobs', 'pendingCount', 'failedCount'));
    }

    /**
     * Purge la file d'attente.
     */
    public function clearQueue()
    {
        \Illuminate\Support\Facades\DB::connection('queue')->table('jobs')->delete();
        return back()->with('success', 'File d\'attente purgée avec succès.');
    }

    /**
     * Supprime un job spécifique.
     */
    public function deleteJob($id)
    {
        \Illuminate\Support\Facades\DB::connection('queue')->table('jobs')->where('id', $id)->delete();
        return back()->with('success', "Job #{$id} supprimé.");
    }

    /**
     * Re-lance un job échoué.
     */
    public function retryJob($id)
    {
        // En Laravel, on utilise généralement artisan queue:retry {id}
        // Mais ici on peut essayer de le réinsérer manuellement ou via Artisan
        \Illuminate\Support\Facades\Artisan::call('queue:retry', ['id' => [$id]]);
        return back()->with('success', "Tentative de relance du job #{$id}.");
    }

    /**
     * Purge les jobs en échec.
     */
    public function clearFailedJobs()
    {
        \Illuminate\Support\Facades\DB::connection('queue')->table('failed_jobs')->delete();
        return back()->with('success', 'Historique des échecs purgé.');
    }

    /**
     * Affiche la page des paramètres.
     */
    public function settings()
    {
        $settings = \App\Models\Setting::whereIn('group', ['ai_pricing', 'ai_limits'])->get();
        return view('admin.settings', compact('settings'));
    }

    /**
     * Met à jour les paramètres.
     */
    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'required|numeric|min:0',
        ]);

        foreach ($data['settings'] as $key => $value) {
            \App\Models\Setting::set($key, $value);
        }

        return back()->with('success', 'Paramètres mis à jour avec succès.');
    }

    /**
     * Affiche les retours utilisateurs.
     */
    public function feedback()
    {
        $feedbacks = \App\Models\UserFeedback::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin.feedback', compact('feedbacks'));
    }
}
