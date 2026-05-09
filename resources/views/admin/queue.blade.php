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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Tâches en attente</p>
                <div class="flex items-end gap-2">
                    <span class="text-4xl font-black text-indigo-600 tabular-nums">{{ number_format($pendingCount) }}</span>
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

        <!-- Liste des Jobs en cours -->
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
    </div>
</x-admin-layout>
