<div class="p-6 bg-white border-b border-slate-100">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-black text-slate-900">Offres Emploi</h1>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Exploration en temps réel</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative group">
                <label for="global-search" class="sr-only">Rechercher</label>
                <input 
                    id="global-search"
                    type="text" 
                    x-model="filters.q" 
                    @input.debounce.500ms="refreshList()"
                    placeholder="Rechercher..." 
                    class="w-48 bg-slate-100 border-0 rounded-xl px-4 py-2 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 transition-all pl-10"
                >
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            
            @php 
                $matchModel = config('services.gemini.models.match');
                $remainingMatch = Auth::user()->getAiRemainingPoints($matchModel);
            @endphp
            <button 
                @click="{{ $remainingMatch > 0 ? 'triggerTopAi()' : '' }}" 
                :disabled="{{ $remainingMatch > 0 ? 'false' : 'true' }}"
                class="p-2 {{ $remainingMatch > 0 ? 'bg-amber-50 text-amber-600 hover:bg-amber-100' : 'bg-slate-100 text-slate-300 cursor-not-allowed' }} rounded-xl transition-all shadow-sm flex items-center gap-2 border {{ $remainingMatch > 0 ? 'border-amber-100' : 'border-slate-200' }} relative group"
            >
                <div class="flex flex-col items-start gap-0">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        <span class="text-[10px] font-black uppercase tracking-widest">Analyse IA (Top 20)</span>
                    </div>
                    @if($remainingMatch > 0)
                        <span class="text-[7px] font-bold opacity-70 ml-6 uppercase tracking-tighter">{{ $remainingMatch }} crédits restants</span>
                    @endif
                </div>
                
                <!-- Tooltip technique sans langue de bois -->
                <div class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-64 p-3 bg-slate-900 text-white text-[10px] font-bold rounded-xl opacity-0 group-hover:opacity-100 transition-all pointer-events-none z-[100] text-left shadow-2xl border border-white/10">
                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 border-8 border-transparent border-b-slate-900"></div>
                    <p class="text-indigo-400 uppercase tracking-widest text-[8px] mb-2 font-black">Audit Sémantique Profond</p>
                    <ul class="space-y-2 list-none p-0">
                        <li class="flex gap-2">
                            <span class="text-indigo-500">▹</span>
                            <span>Confronte vos <strong>récits d'expérience</strong> aux exigences réelles du poste.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-indigo-500">▹</span>
                            <span>Détecte les <strong>soft-skills invisibles</strong> (résilience, adaptabilité) dans votre ton et vos faits.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-indigo-500">▹</span>
                            <span>Valide l'<strong>adéquation culturelle</strong> et la faisabilité contextuelle (hors simples mots-clés).</span>
                        </li>
                    </ul>
                </div>
            </button>
        </div>

    </div>

    <!-- Onglets (Level 5 doc) -->
    <div class="flex p-1 bg-slate-100 rounded-2xl">
        <button 
            @click="filters.sort = 'score_desc'; refreshList()"
            :class="filters.sort === 'score_desc' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
            class="flex-1 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex flex-col items-center justify-center gap-0.5"
        >
            <span>Score Global</span>
            <span class="text-[8px] opacity-60 font-bold">Critères & Conditions</span>
        </button>
        <button 
            @click="filters.sort = 'vector_desc'; refreshList()"
            :class="filters.sort === 'vector_desc' ? 'bg-white text-violet-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
            class="flex-1 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex flex-col items-center justify-center gap-0.5"
        >
            <span>Score Sémantique</span>
            <span class="text-[8px] opacity-60 font-bold">Similarité pure</span>
        </button>
        <button 
            @click="filters.sort = 'ai_desc'; refreshList()"
            :class="filters.sort === 'ai_desc' ? 'bg-white text-emerald-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
            class="flex-1 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex flex-col items-center justify-center gap-0.5"
        >
            <span>Score IA</span>
            <span class="text-[8px] opacity-60 font-bold">Analyse Narrative AI</span>
        </button>
    </div>
</div>
