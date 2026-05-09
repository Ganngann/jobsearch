<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-black text-2xl text-slate-900 leading-tight uppercase tracking-tighter">
            Console d'Administration
        </h2>
    </x-slot>

    <div class="space-y-12">
        <!-- Header Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-1">Utilisateurs Totaux</p>
                <h3 class="text-3xl font-black text-slate-900">{{ $stats['total_users'] }}</h3>
            </div>
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-1">Appels IA (Total)</p>
                <h3 class="text-3xl font-black text-indigo-600">{{ $stats['total_ai_calls'] }}</h3>
            </div>
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-1">Actifs aujourd'hui</p>
                <h3 class="text-3xl font-black text-emerald-500">{{ $stats['active_users_today'] }}</h3>
            </div>
        </div>

        <!-- Maintenance Matching & Vecteurs -->
        <div id="maintenance" class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div>
                    <h2 class="text-xl font-black text-slate-900 uppercase tracking-tighter">Maintenance Sémantique</h2>
                    <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Gérer la santé sémantique de la plateforme</p>
                </div>
                <div class="flex gap-3">
                    <form x-data="{ loading: false }" @submit="loading = true" action="{{ route('admin.matching.vector-sync') }}" method="POST">
                        @csrf
                        <button type="submit" 
                            :disabled="loading"
                            :class="loading ? 'opacity-50 cursor-not-allowed' : ''"
                            class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 flex items-center gap-2">
                            <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="loading ? 'Lancement...' : 'Recalculer les similitudes'"></span>
                        </button>
                    </form>
                    
                    <form x-data="{ loading: false }" @submit="if(confirm('Réinitialiser les scores techniques ?')) { loading = true; return true; } else { return false; }" action="{{ route('admin.matching.clear') }}" method="POST">
                        @csrf
                        <button type="submit" 
                            :disabled="loading"
                            :class="loading ? 'opacity-50 cursor-not-allowed' : ''"
                            class="px-6 py-2.5 bg-rose-50 text-rose-600 border border-rose-100 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-rose-100 transition-all flex items-center gap-2" title="Réinitialiser les scores techniques">
                            <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            <svg x-show="loading" class="animate-spin w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="loading ? 'Reset en cours...' : 'Reset Scores Techniques'"></span>
                        </button>
                    </form>

                    <form x-data="{ loading: false }" @submit="if(confirm('Purger TOUTES les analyses IA ? Cette action est irréversible.')) { loading = true; return true; } else { return false; }" action="{{ route('admin.matching.clear-ai') }}" method="POST">
                        @csrf
                        <button type="submit" 
                            :disabled="loading"
                            :class="loading ? 'opacity-50 cursor-not-allowed' : ''"
                            class="px-6 py-2.5 bg-slate-900 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-black transition-all flex items-center gap-2 shadow-lg shadow-slate-200">
                            <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            <svg x-show="loading" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="loading ? 'Purge en cours...' : 'Purger Analyses IA'"></span>
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 divide-y md:divide-y-0 md:divide-x divide-slate-100 border-b border-slate-100">
                <div class="p-6">
                    <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-1">Couverture Vectorielle</p>
                    <div class="flex items-baseline gap-2">
                        <p class="text-2xl font-black text-slate-900">{{ $stats['jobs_vectorized'] }}</p>
                        <p class="text-xs font-bold text-slate-400">/ {{ $stats['jobs_total'] }} offres</p>
                    </div>
                    <div class="mt-2 w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                        <div class="bg-indigo-500 h-full" style="width: {{ ($stats['jobs_total'] > 0) ? ($stats['jobs_vectorized'] / $stats['jobs_total'] * 100) : 0 }}%"></div>
                    </div>
                </div>
                <div class="p-6">
                    <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-1">Prêt pour Matching (Actifs)</p>
                    <div class="flex items-baseline gap-2">
                        <p class="text-2xl font-black text-emerald-600">{{ $stats['jobs_active_vectorized'] }}</p>
                        <p class="text-xs font-bold text-slate-400">offres</p>
                    </div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-1 italic">Vecteurs OK & Statut Actif</p>
                </div>
                <div class="p-6 bg-amber-50/30">
                    <p class="text-amber-600 text-[10px] font-black uppercase tracking-widest mb-1">En attente de Scan</p>
                    <div class="flex items-baseline gap-3">
                        <p class="text-2xl font-black text-amber-600">{{ $stats['jobs_pending_vectorization'] }}</p>
                        @if($stats['jobs_pending_vectorization'] > 0)
                            <form x-data="{ loading: false }" @submit="loading = true" action="{{ route('admin.matching.scan') }}" method="POST">
                                @csrf
                                <button type="submit" 
                                    :disabled="loading"
                                    :class="loading ? 'opacity-50 cursor-not-allowed' : ''"
                                    class="px-3 py-1 bg-amber-600 text-white text-[10px] font-black uppercase tracking-widest rounded-lg hover:bg-amber-700 transition-all shadow-sm flex items-center gap-1">
                                    <svg x-show="loading" class="animate-spin h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span x-text="loading ? 'Scan...' : 'Lancer Scan'"></span>
                                </button>
                            </form>
                        @endif
                    </div>
                    <p class="text-[10px] text-amber-500/70 font-bold uppercase mt-1">Offres actives sans vecteur</p>
                </div>
                <div class="p-6">
                    <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-1">Expertises IA (Narratives)</p>
                    <p class="text-2xl font-black text-indigo-600">{{ number_format($stats['matches_ai']) }}</p>
                    <p class="text-[10px] text-emerald-500 font-bold uppercase mt-1">Analyses profondes réussies</p>
                </div>
            </div>
        </div>

        <!-- AI Details Stats -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/30">
                <h2 class="text-xl font-black text-slate-900 uppercase tracking-tighter">Consommation IA</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 divide-y md:divide-y-0 md:divide-x divide-slate-100">
                <div class="p-6">
                    <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-1">Tokens Entrants</p>
                    <p class="text-2xl font-black text-slate-900">{{ number_format($stats['total_tokens_in']) }}</p>
                </div>
                <div class="p-6">
                    <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-1">Tokens Sortants</p>
                    <p class="text-2xl font-black text-slate-900">{{ number_format($stats['total_tokens_out']) }}</p>
                </div>
                <div class="p-6">
                    <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-1">Coût Estimé (Flash)</p>
                    <p class="text-2xl font-black text-emerald-600">~{{ number_format(($stats['total_tokens_in'] * 0.0001 / 1000) + ($stats['total_tokens_out'] * 0.0003 / 1000), 4) }} $</p>
                </div>
                <div class="p-6">
                    <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-1">Appels Totaux</p>
                    <p class="text-2xl font-black text-indigo-600">{{ $stats['total_ai_calls'] }}</p>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50">
                            <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Modèle</th>
                            <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Catégorie</th>
                            <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Appels</th>
                            <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Tokens In</th>
                            <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Tokens Out</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-xs">
                        @foreach($stats['ai_details'] as $detail)
                        <tr>
                            <td class="px-6 py-3 font-bold text-slate-700">{{ $detail->model }}</td>
                            <td class="px-6 py-3 text-center">
                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-bold uppercase text-[9px]">{{ $detail->category }}</span>
                            </td>
                            <td class="px-6 py-3 text-center font-bold">{{ $detail->count }}</td>
                            <td class="px-6 py-3 text-right text-slate-500">{{ number_format($detail->total_in) }}</td>
                            <td class="px-6 py-3 text-right text-slate-500">{{ number_format($detail->total_out) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/30">
                <h2 class="text-xl font-black text-slate-900 uppercase tracking-tighter">Gestion des Utilisateurs & Quotas IA</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50">
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Utilisateur</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Appels IA</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Usage Jour</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Limite Quotidienne</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @foreach($users as $user)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center font-bold text-slate-400 overflow-hidden">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 flex items-center gap-2">
                                            {{ $user->name }}
                                            @if($user->is_admin)
                                                <span class="text-[9px] bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded uppercase font-black">Admin</span>
                                            @endif
                                        </p>
                                        <p class="text-xs text-slate-400">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-slate-700">
                                {{ $user->ai_calls_count }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-black {{ $user->daily_ai_usage >= $user->daily_ai_limit ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600' }}">
                                    {{ $user->daily_ai_usage }} / {{ $user->daily_ai_limit }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <form action="{{ route('admin.users.update-limit', $user) }}" method="POST" class="flex items-center gap-2">
                                    @csrf
                                    <input type="number" name="limit" value="{{ $user->daily_ai_limit }}" class="w-20 px-3 py-1.5 bg-slate-50 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500/20">
                                    <button type="submit" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form action="{{ route('admin.users.toggle-admin', $user) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-xs font-black uppercase tracking-tighter {{ $user->is_admin ? 'text-rose-500 hover:text-rose-700' : 'text-indigo-600 hover:text-indigo-800' }}">
                                        {{ $user->is_admin ? 'Retirer Admin' : 'Nommer Admin' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-6 bg-slate-50 border-t border-slate-100">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>
