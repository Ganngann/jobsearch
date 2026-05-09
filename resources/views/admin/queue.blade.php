<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center w-full">
            <h2 class="font-black text-2xl text-slate-900 leading-tight uppercase tracking-tighter">
                Moniteur de File d'Attente
            </h2>
            <div class="flex gap-3">
                <form x-data @submit.prevent="if(confirm('Purger toute l\'historique des erreurs ?')) $el.submit()" action="{{ route('admin.queue.failed.clear') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-slate-200 transition-all flex items-center gap-2">
                        Effacer Erreurs
                    </button>
                </form>

                <form x-data @submit.prevent="if(confirm('Purger toute la file d\'attente ? Cela annulera les tâches de matching en cours.')) $el.submit()" action="{{ route('admin.queue.clear') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-rose-700 transition-all flex items-center gap-2 shadow-lg shadow-rose-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        Purger la file
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="space-y-8">
        <!-- Statistiques Rapides -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-indigo-600 p-6 rounded-3xl border border-indigo-500 shadow-lg shadow-indigo-100">
                <p class="text-[10px] font-black text-indigo-100 uppercase tracking-[0.2em] mb-1">En cours d'exécution</p>
                <div class="flex items-end gap-2">
                    <span class="text-4xl font-black text-white tabular-nums">{{ number_format($activeCount) }}</span>
                    <span class="text-xs font-bold text-indigo-200 mb-1">actifs</span>
                </div>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Tâches en attente</p>
                <div class="flex items-end gap-2">
                    <span class="text-4xl font-black text-slate-900 tabular-nums">{{ number_format($pendingCount) }}</span>
                    <span class="text-xs font-bold text-slate-400 mb-1">jobs</span>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Tâches en échec</p>
                <div class="flex items-end gap-2">
                    <span class="text-4xl font-black {{ $failedCount > 0 ? 'text-rose-600' : 'text-emerald-500' }} tabular-nums">{{ number_format($failedCount) }}</span>
                    <span class="text-xs font-bold text-slate-400 mb-1">erreurs</span>
                </div>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">État SQLite</p>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></div>
                        <span class="text-sm font-black text-slate-700 uppercase tracking-tight">Actif (WAL)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jobs en cours -->
        @if($activeJobs->isNotEmpty())
        <div class="bg-indigo-50/50 rounded-[2rem] border border-indigo-100 shadow-xl shadow-indigo-100/20 overflow-hidden">
            <div class="px-8 py-6 border-b border-indigo-100 flex justify-between items-center bg-indigo-100/20">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center">
                        <div class="flex gap-0.5">
                            <div class="w-1 h-3 bg-white animate-bounce" style="animation-delay: 0s"></div>
                            <div class="w-1 h-4 bg-white animate-bounce" style="animation-delay: 0.1s"></div>
                            <div class="w-1 h-3 bg-white animate-bounce" style="animation-delay: 0.2s"></div>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-black text-indigo-900 uppercase tracking-widest text-sm">Jobs Actifs</h3>
                        <p class="text-[10px] text-indigo-400 font-bold uppercase tracking-widest">En cours de traitement par un worker</p>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[10px] font-black text-indigo-400 uppercase tracking-[0.2em] bg-indigo-50/30">
                            <th class="px-4 py-4 w-20">ID</th>
                            <th class="px-4 py-4">Tâche</th>
                            <th class="px-4 py-4 text-center">Tentative</th>
                            <th class="px-4 py-4 text-right">Depuis</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-indigo-100/50">
                        @foreach($activeJobs as $job)
                            @php 
                                $payload = json_decode($job->payload, true);
                                $rawName = $payload['displayName'] ?? 'UnknownJob';
                                $displayName = str_replace('App\\Jobs\\', '', $rawName);
                            @endphp
                            <tr class="bg-white/50">
                                <td class="px-4 py-4">
                                    <span class="text-[10px] font-black text-indigo-300 tabular-nums">#{{ $job->id }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-widest bg-indigo-600 text-white shadow-sm">
                                            {{ $displayName }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-[10px] font-black text-indigo-400">
                                        {{ $job->attempts }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <span class="text-[9px] font-bold text-indigo-400 tabular-nums">
                                        {{ \Carbon\Carbon::createFromTimestamp($job->reserved_at)->diffForHumans(null, true) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Liste des Jobs en attente -->
        <div class="bg-white rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/50 overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-800 uppercase tracking-widest text-sm">File d'exécution</h3>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Tâches en attente de traitement</p>
                    </div>
                </div>
                <button onclick="window.location.reload()" class="flex items-center gap-2 px-4 py-2 hover:bg-indigo-50 text-indigo-600 rounded-xl transition-all text-[10px] font-black uppercase tracking-widest">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Actualiser
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] bg-slate-50/30">
                            <th class="px-4 py-4 w-20">ID</th>
                            <th class="px-4 py-4">Tâche</th>
                            <th class="px-4 py-4 text-center">Tentatives</th>
                            <th class="px-4 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($pendingJobs as $job)
                            @php 
                                $payload = json_decode($job->payload, true);
                                $rawName = $payload['displayName'] ?? 'UnknownJob';
                                $displayName = str_replace('App\\Jobs\\', '', $rawName);
                                
                                // Couleurs par type de job
                                $colorClass = match($displayName) {
                                    'VectorizeJobOffer' => 'bg-amber-100 text-amber-700',
                                    'AnalyzeJobOffer' => 'bg-indigo-100 text-indigo-700',
                                    'BatchMatchJobOffer' => 'bg-emerald-100 text-emerald-700',
                                    default => 'bg-slate-100 text-slate-700'
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/30 transition-colors group">
                                <td class="px-4 py-4">
                                    <span class="text-[10px] font-black text-slate-300 tabular-nums">#{{ $job->id }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-widest {{ $colorClass }}">
                                            {{ $displayName }}
                                        </span>
                                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-tight truncate hidden md:inline">
                                            Payload OK
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-[10px] font-black text-slate-400">
                                        {{ $job->attempts }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <div class="flex justify-end items-center gap-3">
                                        <span class="text-[9px] font-bold text-slate-300 tabular-nums">
                                            {{ \Carbon\Carbon::createFromTimestamp($job->created_at)->diffForHumans(null, true) }}
                                        </span>
                                        <form action="{{ route('admin.queue.jobs.delete', $job->id) }}" method="POST" x-data @submit.prevent="if(confirm('Supprimer ?')) $el.submit()">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-300 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-all">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-8 py-24 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </div>
                                        <p class="font-black uppercase tracking-[0.3em] text-slate-300 text-xs">File d'attente totalement libérée</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($pendingJobs->hasPages())
            <div class="px-8 py-4 bg-slate-50/50 border-t border-slate-50">
                {{ $pendingJobs->links() }}
            </div>
            @endif
        </div>

        <!-- Jobs en échec -->
        @if($failedCount > 0)
        <div class="bg-rose-50/30 rounded-[2rem] border border-rose-100 overflow-hidden">
            <div class="px-8 py-6 border-b border-rose-100 bg-rose-100/20">
                <h3 class="font-black text-rose-800 uppercase tracking-widest text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Erreurs Critiques (Jobs échoués)
                </h3>
            </div>
            <div class="p-8 space-y-4">
                @foreach($failedJobs as $fjob)
                    <div class="bg-white p-6 rounded-xl border border-rose-100 shadow-sm">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <span class="text-xs font-black text-rose-600 uppercase tracking-tighter">{{ $fjob->failed_at }}</span>
                                <p class="text-[10px] font-bold text-slate-400 mt-1 uppercase">Job ID: {{ $fjob->id }}</p>
                            </div>
                            <form action="{{ route('admin.queue.jobs.retry', $fjob->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all">
                                    Réessayer
                                </button>
                            </form>
                        </div>
                        <div class="text-xs font-mono text-slate-700 bg-slate-50 p-4 rounded-lg border border-slate-100 overflow-x-auto max-h-32">
                            {{ $fjob->exception }}
                        </div>
                    </div>
                @endforeach
            </div>
            
            @if($failedJobs->hasPages())
            <div class="px-8 py-4 bg-rose-100/10 border-t border-rose-100">
                {{ $failedJobs->links() }}
            </div>
            @endif
        </div>
        @endif
        <!-- Tâches Planifiées (Crons) -->
        <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden mt-8">
            <div class="px-8 py-6 border-b border-slate-50 flex items-center gap-4 bg-slate-50/50">
                <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <h3 class="font-black text-slate-800 uppercase tracking-widest text-sm">Tâches Planifiées</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Configuration du planificateur (Schedule)</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] bg-slate-50/30">
                            <th class="px-8 py-4">Commande</th>
                            <th class="px-4 py-4">Fréquence</th>
                            <th class="px-4 py-4 text-center">Activité réelle</th>
                            <th class="px-4 py-4 text-right">Prochaine exécution</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($scheduledTasks as $task)
                            <tr class="hover:bg-slate-50/30 transition-colors">
                                <td class="px-8 py-4">
                                    <code class="text-[10px] font-bold text-slate-600 bg-slate-100 px-2 py-1 rounded">php artisan {{ trim($task['command']) }}</code>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-tight">{{ $task['expression'] }}</span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @if($task['last_activity'])
                                        <div class="flex flex-col items-center">
                                            <span class="text-[10px] font-black {{ \Carbon\Carbon::parse($task['last_activity'])->gt(now()->subMinutes(5)) ? 'text-emerald-500' : 'text-slate-400' }} tabular-nums">
                                                {{ \Carbon\Carbon::parse($task['last_activity'])->diffForHumans() }}
                                            </span>
                                            <div class="flex gap-0.5 mt-1">
                                                <div class="w-1 h-1 rounded-full {{ \Carbon\Carbon::parse($task['last_activity'])->gt(now()->subMinutes(5)) ? 'bg-emerald-500 animate-ping' : 'bg-slate-300' }}"></div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex flex-col items-center">
                                            <span class="text-[10px] font-bold text-slate-300 uppercase tracking-widest italic">Aucune activité récente</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <span class="text-[10px] font-black text-slate-500 tabular-nums">{{ \Carbon\Carbon::parse($task['next_run'])->diffForHumans() }}</span>
                                    <p class="text-[9px] text-slate-300 font-bold tabular-nums">{{ $task['next_run'] }}</p>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Guide Technique des Processus -->
        <div class="mt-12 bg-slate-900 rounded-[2rem] p-10 text-white shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 p-8 opacity-10">
                <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            </div>
            
            <div class="relative z-10">
                <h3 class="text-2xl font-black uppercase tracking-tighter mb-10 flex items-center gap-3">
                    <span class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center text-sm">i</span>
                    Documentation du Système de Fond
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                    <!-- Tâches Planifiées -->
                    <div class="space-y-8">
                        <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-400 pb-2 border-b border-slate-800">Tâches Planifiées (Cron)</h4>
                        
                        <div class="space-y-6">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <code class="text-[10px] font-bold text-amber-400 bg-amber-400/10 px-2 py-0.5 rounded">forem:scan --mode=flash</code>
                                </div>
                                <p class="text-xs text-slate-400 leading-relaxed">
                                    Scanne uniquement la première page de l'API Forem (100 dernières offres). Identifie les nouveaux IDs et crée des enregistrements partiels en base de données. Fréquence : toutes les 5 minutes.
                                </p>
                            </div>

                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <code class="text-[10px] font-bold text-amber-400 bg-amber-400/10 px-2 py-0.5 rounded">forem:scan --mode=cycle</code>
                                </div>
                                <p class="text-xs text-slate-400 leading-relaxed">
                                    Parcourt l'intégralité du catalogue Forem de manière séquentielle (pagination). Met à jour le statut des offres existantes et synchronise les dates de publication. Fréquence : toutes les 15 minutes.
                                </p>
                            </div>

                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <code class="text-[10px] font-bold text-indigo-400 bg-indigo-400/10 px-2 py-0.5 rounded">forem:pull-worker</code>
                                </div>
                                <p class="text-xs text-slate-400 leading-relaxed">
                                    Récupère le détail complet (description HTML, compétences ROME, langues, permis) pour chaque offre dont le champ <span class="text-white italic">is_detailed</span> est faux. Une fois les détails obtenus, il déclenche automatiquement le calcul du matching technique.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Jobs de File -->
                    <div class="space-y-8">
                        <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-400 pb-2 border-b border-slate-800">Files d'attente (Asynchrones)</h4>
                        
                        <div class="space-y-6">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-[10px] font-bold text-white uppercase tracking-wider">BatchMatchJobOffer</span>
                                </div>
                                <p class="text-xs text-slate-400 leading-relaxed">
                                    Calcule les scores de friction pour un utilisateur (distance géographique, conformité légale, compétences requises, vétusté). S'exécute dès qu'une offre est complétée par le pull-worker.
                                </p>
                            </div>

                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-[10px] font-bold text-white uppercase tracking-wider">VectorizeJobOffer</span>
                                </div>
                                <p class="text-xs text-slate-400 leading-relaxed">
                                    Génère les embeddings vectoriels via l'API Google Gemini. Requis pour le calcul de la similarité sémantique (compréhension contextuelle du poste).
                                </p>
                            </div>

                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-[10px] font-bold text-white uppercase tracking-wider">AnalyzeJobOffer</span>
                                </div>
                                <p class="text-xs text-slate-400 leading-relaxed">
                                    Analyse narrative profonde effectuée par Gemini. Compare le récit d'expérience du candidat avec les exigences implicites du poste. Génère les points forts, points faibles et recommandations.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-12 pt-8 border-t border-slate-800 text-center">
                    <p class="text-[10px] text-slate-500 uppercase tracking-widest font-black">
                        Système d'ingestion et d'analyse automatisé — JobSearch Core v1.0
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
