<div class="h-full flex flex-col bg-white overflow-hidden">
    <!-- Header -->
    <div class="p-8 border-b border-slate-100 bg-slate-50/50">
        <div class="flex items-start justify-between gap-6">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-3" x-data="{ 
                    isPreferred: {{ auth()->user()->preferredMetiers->contains($jobOffer->metier_id) ? 'true' : 'false' }},
                    isBlacklisted: {{ auth()->user()->blacklistedMetiers->contains($jobOffer->metier_id) ? 'true' : 'false' }},
                    async toggleFavorite() {
                        const action = this.isPreferred ? 'remove' : 'add';
                        await fetch(`/profile/metiers/{{ $jobOffer->metier_id }}/${action}`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                        });
                        this.isPreferred = !this.isPreferred;
                        if(this.isPreferred) this.isBlacklisted = false;
                        // On rafraîchit la sidebar pour voir le changement
                        if(window.dashboard) window.dashboard.refreshList();
                    },
                    async blacklist() {
                        await fetch(`/profile/metiers/{{ $jobOffer->metier_id }}/blacklist`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                        });
                        this.isBlacklisted = true;
                        this.isPreferred = false;
                        // Refresh dashboard to hide this metier
                        window.location.reload(); 
                    }
                }">
                    <div class="flex items-center bg-indigo-50 rounded-full p-1 border border-indigo-100">
                        <span class="px-3 py-1 text-indigo-700 text-[10px] font-black uppercase tracking-widest">
                            {{ $jobOffer->metier->label ?? 'Métier non spécifié' }}
                        </span>
                        <div class="flex items-center gap-1 border-l border-indigo-200 ml-1 pl-1">
                            <button @click="toggleFavorite()" :class="isPreferred ? 'text-rose-500 bg-rose-50' : 'text-slate-400 hover:text-rose-500'" class="p-1 rounded-full transition-all" title="Ajouter aux favoris">
                                <svg class="w-3.5 h-3.5" :fill="isPreferred ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                            </button>
                            <button @click="blacklist()" class="p-1 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-full transition-all" title="Ne plus voir ce métier">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                            </button>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-slate-200 text-slate-600 text-[10px] font-black uppercase tracking-widest">
                        {{ $jobOffer->forem_ref }}
                    </span>
                </div>
                <h2 class="text-3xl font-black text-slate-900 leading-tight">
                    {{ $jobOffer->title }}
                </h2>
                <div class="mt-4 flex items-center gap-6">
                    <div class="flex items-center gap-2 text-slate-500">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-7h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <span class="text-sm font-bold">{{ $jobOffer->employer->label }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-slate-500">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span class="text-sm font-bold">{{ $jobOffer->location }}</span>
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col items-end gap-4">
                <div class="flex items-center gap-4">
                    <!-- Score Data -->
                    <div class="text-center p-4 bg-white rounded-2xl border border-slate-100 shadow-sm min-w-[100px]">
                        <p class="text-3xl font-black {{ $match->pre_score >= 70 ? 'text-emerald-500' : ($match->pre_score >= 40 ? 'text-amber-500' : 'text-slate-400') }}">
                            {{ $match->pre_score }}<span class="text-xs">%</span>
                        </p>
                        <p class="text-[8px] font-black uppercase tracking-widest text-slate-400 mt-1">Data Match</p>
                    </div>

                    @if($match->ai_status === 'completed')
                        <div id="ai-result-ready" data-score="{{ $match->final_score }}" class="hidden"></div>
                        <!-- Score IA -->
                        <div class="text-center p-4 bg-indigo-600 rounded-2xl shadow-lg shadow-indigo-100 min-w-[100px]">
                            <p class="text-3xl font-black text-white">
                                {{ $match->final_score }}<span class="text-xs">%</span>
                            </p>
                            <p class="text-[8px] font-black uppercase tracking-widest text-indigo-200 mt-1">IA Match</p>
                        </div>
                    @elseif($match->ai_status === 'processing')
                        <!-- État en cours -->
                        <div class="text-center p-4 bg-indigo-50 rounded-2xl border border-indigo-100 min-w-[100px] animate-pulse">
                            <div class="flex justify-center mb-1">
                                <svg class="w-6 h-6 text-indigo-500 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            </div>
                            <p class="text-[8px] font-black uppercase tracking-widest text-indigo-400">Analyse IA...</p>
                        </div>
                    @elseif($match->ai_status === 'failed')
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

                @if($match->ai_status === 'idle' || $match->ai_status === 'failed')
                    <button @click="startAiAnalysis('{{ $jobOffer->forem_id }}')" class="w-full py-3 px-6 bg-indigo-600 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                        {{ $match->ai_status === 'failed' ? 'Réessayer l\'analyse' : 'Lancer Analyse IA' }}
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto p-8 space-y-10 custom-scrollbar">
        @if($match->ai_status === 'completed')
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
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Analyse des compétences</h3>
                    <div class="space-y-3">
                        @foreach($jobOffer->skills as $skill)
                            @php
                                $hasSkill = $user->skills->contains($skill->id);
                            @endphp
                            <div class="flex items-center justify-between p-3 rounded-xl border {{ $hasSkill ? 'bg-emerald-50 border-emerald-100 text-emerald-700' : 'bg-slate-50 border-slate-100 text-slate-400' }}">
                                <span class="text-xs font-bold">{{ $skill->label }}</span>
                                @if($hasSkill)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                @else
                                    <span class="text-[10px] font-black uppercase">Manquant</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Prérequis & Permis</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($jobOffer->permits as $permit)
                            @php $hasPermit = $user->permits->contains($permit->id); @endphp
                            <span class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase border {{ $hasPermit ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700' }}">
                                Permis {{ $permit->label }}
                            </span>
                        @endforeach
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
