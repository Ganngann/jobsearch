<div class="lg:col-span-2 bg-white rounded-3xl p-6 shadow-xl border border-slate-100 flex flex-col min-h-[200px]"
     x-data="{
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
        }
     }">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-black text-slate-900 flex items-center gap-2">
            <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
            Mes métiers cibles
        </h3>
        <span class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-full text-[10px] font-black uppercase tracking-widest" x-text="$store.discovery.savedMetiers.length + ' sélectionnés'"></span>
    </div>

    <div class="flex flex-wrap gap-3">
        <template x-for="item in $store.discovery.savedMetiers" :key="item.type + '-' + (item.id || item.code)">
            <div 
                class="inline-flex items-center gap-2 px-4 py-2 bg-slate-50 border border-slate-100 rounded-2xl group hover:border-red-200 hover:bg-red-50 transition-all"
                :class="item.type === 'family' ? 'ring-2 ring-indigo-100 ring-offset-2' : ''"
            >
                <div class="flex flex-col">
                    <span class="text-xs font-black text-slate-700 group-hover:text-red-700 transition-colors" x-text="item.title"></span>
                    <span class="text-[9px] text-slate-400 uppercase tracking-tighter" x-text="item.type === 'family' ? 'Famille ' + item.code : item.code"></span>
                </div>
                <button 
                    @click="remove(item)"
                    class="p-1 text-slate-300 hover:text-red-500 transition-colors"
                    title="Retirer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </template>
        <div x-show="$store.discovery.savedMetiers.length === 0" class="w-full py-8 text-center border-2 border-dashed border-slate-100 rounded-2xl text-slate-400 text-sm italic">
            Aucun métier sélectionné pour le moment.
        </div>
    </div>
</div>
