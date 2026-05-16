<div class="lg:col-span-2 bg-white rounded-3xl p-6 shadow-xl border border-slate-100 flex flex-col min-h-[300px]"
     x-data="{
        activeTab: 'favorite',
        async remove(item) {
            const url = item.type === 'family' 
                ? `/discovery/referentiel/${item.code}/status`
                : `/discovery/metiers/${item.id}/status`;
            
            const data = await $store.discovery.post(url, { status: 'none' });
            if (data?.status === 'success') {
                $store.discovery.removeSaved(item);
                $store.discovery.updateSuggestionStatus(item, 'none');
                window.dispatchEvent(new CustomEvent('metier-removed'));
            }
        },
        getCount(status) {
            return $store.discovery.savedMetiers.filter(m => m.status === status).length;
        }
     }">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-8 gap-4">
        <h3 class="text-lg font-black text-slate-900 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Gestion des métiers
        </h3>

        <div class="flex bg-slate-100 p-1 rounded-2xl overflow-x-auto no-scrollbar">
            <button @click="activeTab = 'favorite'" 
                    :class="activeTab === 'favorite' ? 'bg-white text-red-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    class="whitespace-nowrap px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
                Favoris
                <span class="bg-red-50 text-red-600 px-2 py-0.5 rounded-lg" x-text="getCount('favorite')"></span>
            </button>
            <button @click="activeTab = 'neutral'" 
                    :class="activeTab === 'neutral' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    class="whitespace-nowrap px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
                Neutres
                <span class="bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-lg" x-text="getCount('neutral')"></span>
            </button>
            <button @click="activeTab = 'refused'" 
                    :class="activeTab === 'refused' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    class="whitespace-nowrap px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
                Blacklist
                <span class="bg-slate-200 text-slate-700 px-2 py-0.5 rounded-lg" x-text="getCount('refused')"></span>
            </button>
        </div>
    </div>

    <div class="flex flex-wrap gap-3">
        <template x-for="item in $store.discovery.savedMetiers.filter(m => m.status === activeTab)" :key="item.type + '-' + (item.id || item.code)">
            <div 
                class="inline-flex items-center gap-2 px-4 py-2 bg-slate-50 border border-slate-100 rounded-2xl group transition-all"
                :class="{
                    'hover:border-red-200 hover:bg-red-50': activeTab === 'favorite',
                    'hover:border-indigo-200 hover:bg-indigo-50': activeTab === 'neutral',
                    'hover:border-slate-300 hover:bg-slate-100': activeTab === 'refused',
                    'ring-2 ring-indigo-100 ring-offset-2': item.type === 'family'
                }"
            >
                <div class="flex flex-col">
                    <span class="text-xs font-black text-slate-700 transition-colors" 
                          :class="{
                            'group-hover:text-red-700': activeTab === 'favorite',
                            'group-hover:text-indigo-700': activeTab === 'neutral'
                          }" 
                          x-text="item.title"></span>
                    <span class="text-[9px] text-slate-400 uppercase tracking-tighter" x-text="item.type === 'family' ? 'Famille ' + item.code : item.code"></span>
                </div>
                <button 
                    @click="remove(item)"
                    class="p-1 text-slate-300 hover:text-red-500 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 transition-colors"
                    title="Retirer"
                    aria-label="Retirer"
                >
                    <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </template>
        
        <div x-show="getCount(activeTab) === 0" class="w-full py-12 flex flex-col items-center justify-center border-2 border-dashed border-slate-100 rounded-3xl text-slate-300">
             <svg aria-hidden="true" class="w-12 h-12 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
             <p class="text-sm italic font-medium">Aucun élément dans cette catégorie.</p>
        </div>
    </div>
</div>
