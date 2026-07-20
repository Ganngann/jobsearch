<div x-data="{ metierSearch: '' }">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Explorer par Métier</h3>
    </div>
    
    <div class="mb-4">
        <input 
            type="text" 
            x-model="metierSearch" 
            placeholder="Filtrer un métier..." 
            class="w-full bg-slate-50 border-0 rounded-xl px-4 py-2 text-[11px] font-bold text-slate-600 focus:ring-1 focus:ring-indigo-500 transition-all"
        >
    </div>

    <div class="space-y-1 mb-8">
        <button 
            @click="setMetier(null)"
            :class="(!filters.metier_id && !filters.rome) ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
            class="w-full text-left px-4 py-3 rounded-xl text-sm font-bold transition-all"
        >
            Tous les métiers
        </button>

        <!-- Active ROME Filter Badge -->
        <template x-if="filters.rome">
            <div class="px-4 py-3 bg-violet-100 text-violet-700 rounded-xl text-xs font-black flex items-center justify-between group">
                <span>DÉCOUVERTE : <span x-text="filters.rome"></span></span>
                <button @click="setMetier(null)" class="opacity-50 hover:opacity-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </template>
    </div>

    <div class="space-y-1 max-h-[400px] overflow-y-auto custom-scrollbar pr-1">
        @php $preferredIds = $user->preferredMetiers->pluck('id')->toArray(); @endphp
        @foreach($topMetiers as $metier)
            @php 
                $isDiscovery = !in_array($metier->id, $preferredIds);
            @endphp
            <button 
                x-show='metierSearch === "" || @js(strtolower($metier->label)) /* Security: Enforce proper JS escaping */.includes(metierSearch.toLowerCase())'
                @click="setMetier({{ $metier->id }})"
                :class="filters.metier_id == {{ $metier->id }} ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'text-slate-600 hover:bg-slate-50'"
                class="w-full text-left px-4 py-3 rounded-xl transition-all group relative"
            >
                <div class="flex items-center justify-between">
                    <div class="flex flex-col min-w-0">
                        <span class="text-xs font-bold truncate">{{ $metier->label }}</span>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-[9px] opacity-40 font-black uppercase tracking-tighter">{{ $metier->job_offers_count }} offres</span>
                            @if($isDiscovery)
                                <span class="text-[9px] text-amber-500 font-black uppercase tracking-tighter flex items-center gap-1">
                                    ✨ Explorer
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </button>
        @endforeach
    </div>
</div>
