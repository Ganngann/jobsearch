<div x-data="{ employerSearch: '' }">
    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Top Employeurs</h3>
    
    <div class="mb-4">
        <input 
            type="text" 
            x-model="employerSearch" 
            placeholder="Filtrer un employeur..." 
            aria-label="Filtrer un employeur"
            class="w-full bg-slate-50 border-0 rounded-xl px-4 py-2 text-[11px] font-bold text-slate-600 focus:ring-1 focus:ring-indigo-500 transition-all"
        >
    </div>

    <div class="space-y-1 max-h-[400px] overflow-y-auto custom-scrollbar pr-1">
        <button 
            @click="setEmployer(null)"
            x-show="employerSearch === ''"
            :class="!filters.employer_id ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'text-slate-600 hover:bg-slate-50'"
            class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-between"
        >
            <span>Tous les employeurs</span>
        </button>
        @foreach($topEmployers as $employer)
            <button 
                x-show="employerSearch === '' || '{{ strtolower(addslashes($employer->label)) }}'.includes(employerSearch.toLowerCase())"
                @click="setEmployer({{ $employer->id }})"
                :class="filters.employer_id == {{ $employer->id }} ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'text-slate-600 hover:bg-slate-50'"
                class="w-full text-left px-4 py-3 rounded-xl transition-all group relative"
            >
                <div class="flex items-center justify-between">
                    <div class="flex flex-col min-w-0">
                        <span class="text-xs font-bold truncate">{{ $employer->label }}</span>
                        <span class="text-[9px] opacity-40 font-black uppercase tracking-tighter mt-0.5">{{ $employer->job_offers_count }} offres</span>
                    </div>
                </div>
            </button>
        @endforeach
    </div>
</div>
