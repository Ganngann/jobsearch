<div class="bg-white rounded-3xl p-8 shadow-xl border border-slate-100 flex flex-col relative overflow-hidden group">
    <!-- Badge Type -->
    <div class="absolute top-0 right-0 p-4">
        <span 
            :class="s.type === 'surprise' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'"
            class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"
            x-text="s.type === 'surprise' ? 'Découverte' : 'Alignement'"
        ></span>
    </div>

    <div class="mb-6">
        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-4 font-black">
            <span x-text="s.code"></span>
        </div>
        <h3 class="text-2xl font-black text-slate-900 leading-tight mb-2" x-text="s.title"></h3>
        <p class="text-sm text-slate-400 font-medium" x-text="s.family"></p>
    </div>

    <p class="text-slate-600 leading-relaxed mb-8 flex-grow italic" x-text="'« ' + s.reason + ' »'"></p>

    <!-- Variants List (Always Visible if not empty) -->
    <div class="mb-8 bg-slate-50/50 rounded-2xl p-4 border border-slate-100" x-show="s.variants && s.variants.length > 0">
        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Métiers liés</h4>
        <div class="space-y-2 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
            <template x-for="v in s.variants" :key="v.id">
                <x-discovery.variant-item />
            </template>
        </div>
    </div>

    <div class="flex flex-col gap-4">
        <div class="flex gap-2">
            <!-- Family Favorite (+20) -->
            <button 
                @click="setReferentielStatus(s, 'favorite')"
                :class="s.status === 'favorite' ? 'bg-red-50 text-red-600 border-red-200' : 'bg-slate-50 text-slate-400 border-slate-100'"
                class="flex-1 py-3 rounded-2xl border font-bold transition-all flex flex-col items-center justify-center gap-1 hover:shadow-sm"
                title="Coup de cœur (+20 pts)"
            >
                <svg class="w-5 h-5" :fill="s.status === 'favorite' ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                <span class="text-[9px] uppercase tracking-tighter">Favori</span>
            </button>
            
            <!-- Family Neutral (0) -->
            <button 
                @click="setReferentielStatus(s, 'neutral')"
                :class="s.status === 'neutral' ? 'bg-slate-100 text-slate-600 border-slate-300' : 'bg-slate-50 text-slate-400 border-slate-100'"
                class="flex-1 py-3 rounded-2xl border font-bold transition-all flex flex-col items-center justify-center gap-1 hover:shadow-sm"
                title="Neutre (0 pt)"
            >
                <svg class="w-5 h-5" :fill="s.status === 'neutral' ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span class="text-[9px] uppercase tracking-tighter">Neutre</span>
            </button>

            <!-- Family Refused (-20) -->
            <button 
                @click="setReferentielStatus(s, 'refused')"
                :class="s.status === 'refused' ? 'bg-black text-white border-black' : 'bg-slate-50 text-slate-400 border-slate-100'"
                class="flex-1 py-3 rounded-2xl border font-bold transition-all flex flex-col items-center justify-center gap-1 hover:shadow-sm"
                title="Refuser ce domaine (-20 pts)"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                <span class="text-[9px] uppercase tracking-tighter">Refusé</span>
            </button>
        </div>
        
        <a 
            :href="'/dashboard?rome=' + s.code" 
            class="w-full py-6 bg-indigo-50/50 border border-indigo-100 rounded-3xl flex flex-col items-center justify-center gap-1 transition-all hover:bg-indigo-50 hover:border-indigo-300 group/count"
        >
            <span class="text-3xl font-black text-indigo-600 group-hover:scale-110 transition-transform" x-text="s.offers_count"></span>
            <span class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Offres en base</span>
        </a>
    </div>

    <!-- Blacklisted Overlay -->
    <div x-show="s.is_blacklisted" class="absolute inset-0 bg-slate-50/90 flex flex-col items-center justify-center p-8 text-center backdrop-blur-sm">
        <svg class="w-12 h-12 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
        <p class="font-bold text-slate-500 mb-4 text-sm uppercase tracking-wider">Ce domaine est ignoré</p>
        <button 
            @click="toggleBlacklist(s)"
            class="px-6 py-2 bg-white border border-slate-200 rounded-full text-xs font-black text-indigo-600 shadow-sm hover:shadow-md transition-all"
        >
            RÉACTIVER
        </button>
    </div>
</div>
