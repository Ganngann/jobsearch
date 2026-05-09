<div class="h-full flex flex-col bg-white overflow-hidden">
    <!-- Header -->
    <div class="p-8 border-b border-slate-100 bg-slate-50/50">
        <div class="flex items-start justify-between gap-6">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-3" x-data="{ 
                    isPreferred: {{ $jobOffer->metier_id ? (auth()->user()->preferredMetiers->contains($jobOffer->metier_id) ? 'true' : 'false') : 'false' }},
                    isBlacklisted: {{ $isOfferBlacklisted ? 'true' : 'false' }},
                    matchScore: {{ $match->pre_score ?? 'null' }},
                    init() {
                        if (window.dashboard && this.matchScore !== null) {
                            window.dashboard.updateOfferScore('{{ $jobOffer->forem_id }}', this.matchScore, this.isBlacklisted);
                        }
                    },
                    async toggleFavorite() {
                        @if($jobOffer->metier_id)
                        const status = this.isPreferred ? 'none' : 'favorite';
                        await fetch(`/discovery/metiers/{{ $jobOffer->metier_id }}/status`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({ status })
                        });
                        this.isPreferred = !this.isPreferred;
                        if(this.isPreferred) this.isBlacklisted = false;
                        if(window.dashboard) window.dashboard.refreshList();
                        @endif
                    },
                    async blacklist() {
                        @if($jobOffer->metier_id)
                        await fetch(`/discovery/metiers/{{ $jobOffer->metier_id }}/status`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({ status: 'refused' })
                        });
                        this.isBlacklisted = true;
                        this.isPreferred = false;
                        if(window.dashboard) window.dashboard.refreshList();
                        @endif
                    },
                    async unblacklist() {
                        @if($jobOffer->metier_id)
                        await fetch(`/discovery/metiers/{{ $jobOffer->metier_id }}/status`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({ status: 'none' })
                        });
                        this.isBlacklisted = false;
                        if(window.dashboard) window.dashboard.refreshList();
                        @endif
                    }
                }">
                    @if($jobOffer->metier_id)
                    <div class="flex items-center rounded-full p-1 border transition-all duration-300 shadow-sm" :class="isBlacklisted ? 'bg-rose-50 border-rose-200' : 'bg-indigo-50 border-indigo-100'">
                        <span class="px-3 py-1 text-[10px] font-black uppercase tracking-widest transition-colors duration-300" :class="isBlacklisted ? 'text-rose-700' : 'text-indigo-700'">
                            {{ $jobOffer->metier->label ?? 'Métier non spécifié' }}
                        </span>
                        @if($isParentFavorite)
                            <div class="flex items-center gap-1 bg-rose-500 text-white px-2 py-0.5 rounded-full mr-1 shadow-sm" title="Famille ROME en favoris">
                                <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                <span class="text-[8px] font-black uppercase">Famille</span>
                            </div>
                        @endif
                        <div class="flex items-center gap-1 border-l ml-1 pl-1" :class="isBlacklisted ? 'border-rose-200' : 'border-indigo-200'">
                            <button @click="toggleFavorite()" :class="isPreferred ? 'text-rose-500 bg-rose-50' : 'text-slate-400 hover:text-rose-500'" class="p-1 rounded-full transition-all" title="Ajouter aux favoris">
                                <svg class="w-3.5 h-3.5" :fill="isPreferred ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                            </button>
                            <button @click="isBlacklisted ? unblacklist() : blacklist()" :class="isBlacklisted ? 'text-rose-600 bg-rose-100' : 'text-slate-400 hover:text-rose-500'" class="p-1 rounded-full transition-all" :title="isBlacklisted ? 'Retirer du blacklist' : 'Ne plus voir ce métier'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                            </button>
                        </div>
                    </div>
                    @else
                        <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-400 text-[10px] font-black uppercase tracking-widest">
                            Métier non spécifié
                        </span>
                    @endif
                    <span class="px-3 py-1 rounded-full bg-slate-200 text-slate-600 text-[10px] font-black uppercase tracking-widest">
                        {{ $jobOffer->forem_ref }}
                    </span>
                </div>
                <h2 class="text-3xl font-black text-slate-900 leading-tight">
                    {{ $jobOffer->title }}
                </h2>
                <div class="mt-6 flex flex-wrap gap-3">
                    <!-- Employeur -->
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-slate-50 rounded-xl border border-slate-100">
                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-7h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <span class="text-[10px] font-black uppercase text-slate-700 tracking-wider">{{ $jobOffer->employer->label }}</span>
                    </div>

                    <!-- Contrat -->
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-indigo-50 rounded-xl border border-indigo-100">
                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span class="text-[10px] font-black uppercase text-indigo-700 tracking-wider">{{ $jobOffer->contract_type }}</span>
                    </div>

                    <!-- Localisation -->
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-slate-50 rounded-xl border border-slate-100">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                        <span class="text-[10px] font-black uppercase text-slate-600 tracking-wider">{{ $jobOffer->location }}</span>
                    </div>

                    <!-- Expérience -->
                    @if($jobOffer->experience_required)
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-slate-50 rounded-xl border border-slate-100">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <span class="text-[10px] font-black uppercase text-slate-600 tracking-wider">{{ $jobOffer->experience_required }}</span>
                    </div>
                    @endif

                    <!-- Salaire -->
                    @if($jobOffer->salary)
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-emerald-50 rounded-xl border border-emerald-100">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M12 8V7"></path></svg>
                        <span class="text-[10px] font-black uppercase text-emerald-700 tracking-wider">{{ $jobOffer->salary }}</span>
                    </div>
                    @endif
                </div>
            </div>
            
            <div class="flex flex-col items-end gap-4">
                <div class="flex items-center gap-4">
                    <!-- Score Confort avec Tooltip -->
                    <div class="relative group cursor-help">
                        <div class="text-center p-4 bg-white rounded-2xl border border-slate-100 shadow-sm min-w-[120px] hover:border-emerald-200 transition-all">
                            @if($match->exists)
                                <p class="text-3xl font-black {{ ($match->pre_score ?? 0) >= 70 ? 'text-emerald-500' : (($match->pre_score ?? 0) >= 40 ? 'text-amber-500' : 'text-slate-400') }}">
                                    {{ $match->pre_score ?? 0 }}<span class="text-xs">%</span>
                                </p>
                            @else
                                <p class="text-3xl font-black text-slate-300">...</p>
                            @endif
                            <p class="text-[8px] font-black uppercase tracking-widest text-slate-400 mt-1">{{ $match->exists ? 'Score Global' : 'Calcul...' }}</p>
                        </div>

                        <!-- Tooltip Detail Riche -->
                        @if($match && $match->pre_score_details)
                        <div class="absolute right-full top-0 mr-4 w-80 p-5 bg-slate-900 text-white rounded-2xl shadow-2xl opacity-0 group-hover:opacity-100 pointer-events-none transition-all z-[100] transform translate-y-0">
                            <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4 pb-2 border-b border-white/10">Pourquoi ce score ?</h4>
                            
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-bold text-slate-300 uppercase tracking-tight">Base de départ</span>
                                    <span class="text-[11px] font-black text-emerald-400">+{{ $match->pre_score_details['base'] ?? 100 }}</span>
                                </div>

                                @if(!empty($match->pre_score_details['penalties']))
                                    <div class="space-y-4">
                                        @foreach($match->pre_score_details['penalties'] as $penalty)
                                            <div class="p-3 bg-white/5 rounded-xl border border-white/10">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="text-[9px] font-black text-rose-400 uppercase tracking-widest">DÉDUCTION</span>
                                                    <span class="text-[11px] font-black text-rose-400">-{{ abs($penalty['value']) }}</span>
                                                </div>
                                                <p class="text-[11px] font-bold text-slate-200 leading-snug">
                                                    @if(($penalty['type'] ?? '') === 'distance')
                                                        Mobilité : Distance de {{ round($penalty['meta']['distance'] ?? 0, 1) }} km (Rayon max : {{ $user->radius ?? 30 }}km).
                                                    @elseif(($penalty['type'] ?? '') === 'permit_missing')
                                                        Légal : Permis de conduire manquant.
                                                    @elseif(($penalty['type'] ?? '') === 'language_missing')
                                                        Linguistique : Langue requise non maîtrisée.
                                                     @elseif(($penalty['type'] ?? '') === 'freshness')
                                                        Vétusté : Offre de plus de 15 jours.
                                                    @else
                                                        {{ $penalty['label'] }}
                                                     @endif
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif($match->pre_score < 100)
                                    <div class="p-4 bg-amber-900/20 rounded-xl border border-amber-500/20 text-center">
                                        <p class="text-[10px] font-bold text-amber-300">Détails du score indisponibles.</p>
                                        <p class="text-[9px] text-amber-400/60 mt-1 uppercase font-black">Un recalcul est nécessaire pour afficher les facteurs de friction.</p>
                                    </div>
                                @else
                                    <div class="p-4 bg-white/5 rounded-xl border border-white/10 text-center">
                                        <p class="text-[10px] font-bold text-slate-400 italic">Aucune friction détectée sur vos critères.</p>
                                    </div>
                                @endif

                                @if(!empty($match->pre_score_details['bonuses']))
                                    <div class="mt-4 pt-4 border-t border-white/10 space-y-3">
                                        @foreach($match->pre_score_details['bonuses'] as $bonus)
                                            <div class="flex items-center justify-between">
                                                <span class="text-[10px] font-bold text-emerald-300 uppercase">{{ $bonus['label'] }}</span>
                                                <span class="text-[11px] font-black text-emerald-400">+{{ $bonus['value'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="mt-6 pt-4 border-t border-white/10 flex items-center justify-between opacity-40">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Fiabilité du Calcul</span>
                                    <span class="text-[10px] font-black text-slate-300">Précision : {{ $user->profile_completion }}%</span>
                                </div>

                            </div>
                            
                            <!-- Flèche vers la droite -->
                            <div class="absolute left-full top-8 -translate-y-1/2 border-8 border-transparent border-l-slate-900"></div>
                        </div>
                        @else
                        <div class="absolute right-full top-0 mr-4 w-48 p-3 bg-slate-900 text-white rounded-xl shadow-2xl opacity-0 group-hover:opacity-100 pointer-events-none transition-all z-[100] text-center">
                             <span class="text-[10px] font-bold italic">Calcul des détails...</span>
                             <div class="absolute left-full top-8 -translate-y-1/2 border-8 border-transparent border-l-slate-900"></div>
                        </div>
                        @endif
                    </div>

                    <!-- Potentiel Métier (Sémantique) -->
                    @if($match && $match->vector_score !== null)
                        <div class="text-center p-4 bg-blue-600 rounded-2xl shadow-lg shadow-blue-100 min-w-[120px]">
                            <p class="text-3xl font-black text-white">
                                {{ round($match->vector_score) }}<span class="text-xs">%</span>
                            </p>
                            <p class="text-[8px] font-black uppercase tracking-widest text-blue-200 mt-1">Potentiel Métier</p>
                        </div>
                    @endif


                    @php
                        $isAiStale = $match && $match->ai_status === 'processing' && $match->updated_at->lt(now()->subMinutes(10));
                    @endphp

                    @if($match && ($match->ai_status === 'completed' || ($match->final_score > 0 && !$isAiStale)))
                        <div id="ai-result-ready" data-score="{{ $match->final_score }}" class="hidden"></div>
                        <!-- Score IA -->
                        <div class="text-center p-4 bg-indigo-600 rounded-2xl shadow-lg shadow-indigo-100 min-w-[100px]">
                            <p class="text-3xl font-black text-white">
                                {{ $match->final_score }}<span class="text-xs">%</span>
                            </p>
                            <p class="text-[8px] font-black uppercase tracking-widest text-indigo-200 mt-1">IA Match</p>
                        </div>
                    @elseif($match && $match->ai_status === 'processing')
                        <!-- État en cours -->
                        <div class="text-center p-4 bg-indigo-50 rounded-2xl border border-indigo-100 min-w-[100px] animate-pulse">
                            <div class="flex justify-center mb-1">
                                <svg class="w-6 h-6 text-indigo-500 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            </div>
                            <p class="text-[8px] font-black uppercase tracking-widest text-indigo-400">Analyse IA...</p>
                        </div>
                    @elseif($match && $match->ai_status === 'failed')
                        <div id="ai-result-failed" class="hidden"></div>
                        <!-- État Erreur -->
                        <div class="text-center p-4 bg-rose-50 rounded-2xl border border-rose-100 min-w-[100px]">
                            <div class="flex justify-center mb-1">
                                <svg class="w-6 h-6 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                            <p class="text-[8px] font-black uppercase tracking-widest text-rose-400">Échec IA</p>
                        </div>
                    @endif
                </div>

                <div class="w-full flex gap-2">
                    <button 
                        @click="startAiAnalysis('{{ $jobOffer->forem_id }}')" 
                        :disabled="previewLoading"
                        class="flex-1 py-3 px-6 rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-lg flex items-center justify-center gap-2 {{ ($match && $match->final_score > 0 && !$isAiStale) ? 'bg-indigo-50 text-indigo-600 border border-indigo-100 hover:bg-indigo-100 shadow-indigo-50' : 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-indigo-100' }}"
                    >
                        <template x-if="previewLoading">
                            <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        </template>
                        <span x-text="previewLoading ? 'Lancement...' : '{{ ($match && $match->final_score > 0 && !$isAiStale) ? 'Relancer IA' : 'Lancer Analyse IA' }}'"></span>
                    </button>

                    <button 
                        @click="embedJob('{{ $jobOffer->forem_id }}')" 
                        :disabled="previewLoading"
                        class="py-3 px-4 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center border {{ $jobOffer->vector_embedding ? 'bg-indigo-50 text-indigo-600 border-indigo-100 hover:bg-indigo-100' : 'bg-slate-100 text-slate-600 border-transparent hover:bg-slate-200' }}"
                        title="{{ $jobOffer->vector_embedding ? 'Mettre à jour le vecteur' : 'Calculer le vecteur (Embedding)' }}"
                    >
                        <svg class="w-4 h-4" :class="previewLoading ? 'animate-pulse' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        <span class="ml-2">{{ $jobOffer->vector_embedding ? 'Update Vecteur' : 'Vectoriser' }}</span>
                    </button>
                </div>
            </div>
            </div>
        </div>

    <!-- Embedding Status -->
    @if($jobOffer->vector_embedding)
        <div class="px-8 py-2 bg-emerald-50 border-y border-emerald-100 flex items-center justify-between">
            <span class="text-[9px] font-black uppercase text-emerald-700 tracking-widest flex items-center gap-1">
                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                Vecteur disponible
            </span>
            @if($match && $match->vector_score !== null)
                <span class="text-[9px] font-black uppercase text-emerald-700 tracking-widest">
                    Similitude vectorielle : {{ number_format($match->vector_score, 1) }}%
                </span>
            @endif
        </div>
    @endif

    <!-- Content -->
    <div class="flex-1 overflow-y-auto p-8 space-y-10 custom-scrollbar">
        @if($match && $match->ai_status === 'completed')
            <div id="ai-result-ready" class="hidden"></div>
            <!-- IA Narrative -->
            <div class="p-6 bg-indigo-50 border border-indigo-100 rounded-3xl relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <svg class="w-20 h-20 text-indigo-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71z"></path></svg>
                </div>
                
                <h3 class="text-sm font-black text-indigo-900 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <span class="w-2 h-2 bg-indigo-500 rounded-full animate-pulse"></span>
                    Analyse Flash
                </h3>
                
                <p class="text-indigo-800 leading-relaxed font-bold text-sm mb-6">
                    {{ $match->ai_analysis_narrative }}
                </p>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <!-- Points Forts -->
                    @if(!empty($match->strengths))
                        <div class="space-y-2">
                            <h4 class="text-[9px] font-black uppercase text-emerald-600 tracking-widest">Points Forts</h4>
                            <ul class="space-y-1">
                                @foreach($match->strengths as $strength)
                                    <li class="text-[11px] text-emerald-800 flex items-start gap-1.5 font-bold">
                                        <svg class="w-3 h-3 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        {{ $strength }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Points Faibles -->
                    @if(!empty($match->weaknesses))
                        <div class="space-y-2">
                            <h4 class="text-[9px] font-black uppercase text-rose-600 tracking-widest">Points Faibles</h4>
                            <ul class="space-y-1">
                                @foreach($match->weaknesses as $weakness)
                                    <li class="text-[11px] text-rose-800 flex items-start gap-1.5 font-bold">
                                        <svg class="w-3 h-3 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        {{ $weakness }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="pt-4 border-t border-indigo-200/50">
                    <p class="text-[11px] text-indigo-900 italic font-black">
                        💡 {{ $match->ai_recommendation }}
                    </p>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-2 gap-8">
            <!-- Details Section -->
            <div class="space-y-8">
                <div>
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Description du poste</h3>
                    <div class="prose prose-slate max-w-none text-slate-600 text-sm leading-relaxed">
                        {!! $jobOffer->description !!}
                    </div>
                </div>
            </div>

            <!-- Profile Match Section -->
            <div class="space-y-8">
                <div>
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Analyse des compétences (JIT)</h3>
                    <div class="space-y-3">
                        @foreach($jobOffer->skills as $skill)
                            @php
                                $userSkill = $user->skills->where('id', $skill->id)->first();
                                $status = $userSkill ? $userSkill->pivot->status : 'none';
                            @endphp
                            <div x-data="{ 
                                    status: '{{ $status }}',
                                    async cycleStatus() {
                                        // Cycle: none (neutral) -> active (validé) -> refused (écarter) -> none
                                        let nextStatus = 'none';
                                        if (this.status === 'none') nextStatus = 'active';
                                        else if (this.status === 'active') nextStatus = 'refused';
                                        else if (this.status === 'refused') nextStatus = 'none';
                                        
                                        const url = '{{ route('profile.skills.status', $skill) }}';
                                        const res = await fetch(url, {
                                            method: 'POST',
                                            headers: { 
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                                                'Accept': 'application/json',
                                                'Content-Type': 'application/json'
                                            },
                                            body: JSON.stringify({ status: nextStatus })
                                        });
                                        if (res.ok) {
                                            this.status = nextStatus;
                                            if(window.dashboard) {
                                                const messages = {
                                                     'active': 'Compétence ajoutée à votre profil !',
                                                     'refused': 'Compétence écartée. Je pénaliserai ces offres.',
                                                     'none': 'Préférence réinitialisée.'
                                                 };
                                                 window.dashboard.showToast(messages[nextStatus], 'success');
                                                // On rafraîchit la liste pour mettre à jour les scores globaux
                                                window.dashboard.refreshList();
                                                // On rafraîchit la preview pour voir le détail du score mis à jour
                                                setTimeout(() => window.dashboard.selectOffer('{{ $jobOffer->forem_id }}'), 100);
                                            }
                                        }
                                    }
                                 }" 
                                 @click="cycleStatus()"
                                 class="flex items-center justify-between p-3 rounded-xl border transition-all duration-300 cursor-pointer group"
                                 :class="{
                                    'bg-emerald-50 border-emerald-100 text-emerald-700 shadow-sm': status === 'active',
                                    'bg-rose-50 border-rose-100 text-rose-700 shadow-sm': status === 'refused',
                                    'bg-slate-50 border-slate-100 text-slate-400': status === 'none'
                                 }">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full" :class="{
                                        'bg-emerald-500 animate-pulse': status === 'active',
                                        'bg-rose-500': status === 'refused',
                                        'bg-slate-300': status === 'none'
                                    }"></div>
                                    <span class="text-xs font-bold">{{ $skill->label }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[9px] font-black uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-opacity" :class="{
                                        'text-emerald-600': status === 'active',
                                        'text-rose-600': status === 'refused',
                                        'text-slate-400': status === 'none'
                                    }">
                                        <span x-show="status === 'none'">Inconnue (+0)</span>
                                        <span x-show="status === 'active'">Maîtrisée (+1)</span>
                                        <span x-show="status === 'refused'">Refusée (-5)</span>
                                    </span>
                                    <div class="p-1 rounded-lg bg-white/50 border border-transparent group-hover:border-slate-200">
                                        <svg x-show="status === 'active'" class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        <svg x-show="status === 'refused'" class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        <svg x-show="status === 'none'" class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>


                <div>
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Prérequis & Permis</h3>
                    <div class="flex flex-wrap gap-2 mb-8">
                        @forelse($jobOffer->permits as $permit)
                            @php $hasPermit = $user->permits->contains($permit->id); @endphp
                            <span class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase border {{ $hasPermit ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700' }}">
                                Permis {{ $permit->label }}
                            </span>
                        @empty
                            <span class="text-[10px] font-bold text-slate-400 italic">Aucun permis spécifié</span>
                        @endforelse
                    </div>

                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Langues</h3>
                    <div class="space-y-2">
                        @forelse($jobOffer->languages as $lang)
                            @php 
                                $userLang = $user->languages->where('id', $lang->id)->first();
                                $hasLang = !is_null($userLang);
                            @endphp
                            <div class="flex items-center justify-between p-2 rounded-lg border {{ $hasLang ? 'bg-emerald-50 border-emerald-100 text-emerald-700' : 'bg-slate-50 border-slate-100 text-slate-400' }}">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold">{{ $lang->label }}</span>
                                    @if($lang->pivot->level)
                                        <span class="text-[9px] font-black uppercase opacity-60">({{ $lang->pivot->level }})</span>
                                    @endif
                                </div>
                                @if($hasLang)
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                @else
                                    <span class="text-[10px] font-black uppercase opacity-40 italic">Manquant</span>
                                @endif
                            </div>
                        @empty
                            <span class="text-[10px] font-bold text-slate-400 italic">Aucune langue spécifique requise</span>
                        @endforelse
                    </div>

                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mt-8 mb-4">Secteurs</h3>
                    <div class="flex flex-wrap gap-2">
                        @forelse($jobOffer->sectors as $sector)
                            <span class="px-2 py-1 rounded-md bg-slate-100 text-slate-600 text-[9px] font-bold uppercase tracking-wider">
                                {{ $sector->label }}
                            </span>
                        @empty
                            <span class="text-[10px] font-bold text-slate-400 italic">Non spécifié</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="p-6 border-t border-slate-100 bg-white flex items-center justify-between">
        <a href="{{ route('jobs.show', $jobOffer) }}" class="text-indigo-600 text-sm font-black uppercase tracking-widest hover:text-indigo-800 transition-colors">
            Voir la fiche complète
        </a>
        <div class="flex items-center gap-4">
            <button class="p-3 rounded-xl border border-slate-200 hover:bg-slate-50 transition-all text-slate-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
            </button>
            <a href="{{ $jobOffer->apply_url ?? '#' }}" target="_blank" class="px-8 py-3 bg-slate-900 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-xl shadow-slate-200">
                Postuler
            </a>
        </div>
    </div>
</div>
