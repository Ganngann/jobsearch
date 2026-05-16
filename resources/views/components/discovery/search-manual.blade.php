<div class="lg:col-span-1 bg-white rounded-3xl p-6 shadow-xl border border-slate-100 flex flex-col"
     x-data="{
        searchQuery: '',
        searchResults: [],
        searching: false,
        async searchMetiers() {
            if (this.searchQuery.length < 2) return this.searchResults = [];
            this.searching = true;
            const data = await $store.discovery.get(`/api/metiers/search?q=${encodeURIComponent(this.searchQuery)}`);
            this.searchResults = data || [];
            this.searching = false;
        },
        async addMetier(res) {
            const data = await $store.discovery.post(`/discovery/metiers/${res.id}/status`, { status: 'favorite' });
            if (data?.status === 'success') {
                $store.discovery.addSaved({ id: res.id, code: res.code, title: res.label, type: 'specific', status: 'favorite' });
                $store.discovery.updateSuggestionStatus(res, 'favorite');
                this.searchQuery = '';
                this.searchResults = [];
                window.dispatchEvent(new CustomEvent('metier-added'));
            }
        }
     }">
    <h3 class="text-lg font-black text-slate-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        Ajouter manuellement
    </h3>
    <div class="relative mb-4">
        <label for="manual-search" class="sr-only">Rechercher un métier</label>
        <input 
            id="manual-search"
            type="text" 
            x-model="searchQuery" 
            @input.debounce.300ms="searchMetiers()"
            placeholder="Rechercher un métier..."
            class="w-full pl-4 pr-10 py-3 bg-slate-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500 transition-all"
        >
        <div class="absolute right-3 top-1/2 -translate-y-1/2" x-show="searching">
            <svg class="animate-spin h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        </div>
    </div>

    <!-- Résultats Recherche -->
    <div class="flex-grow overflow-y-auto max-h-60 space-y-2 custom-scrollbar" x-show="searchResults.length > 0">
        <template x-for="res in searchResults" :key="res.id">
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl hover:bg-indigo-50 transition-colors group">
                <div class="flex flex-col">
                    <span class="text-xs font-bold text-slate-700" x-text="res.label"></span>
                    <span class="text-[10px] text-slate-400 font-medium" x-text="res.code"></span>
                </div>
                <button 
                    @click="addMetier(res)"
                    class="p-2 bg-white text-indigo-600 rounded-lg shadow-sm opacity-0 group-hover:opacity-100 focus-visible:opacity-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 transition-all hover:bg-indigo-600 hover:text-white"
                    aria-label="Ajouter ce métier"
                >
                    <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </button>
            </div>
        </template>
    </div>
    <div x-show="searchQuery.length >= 2 && searchResults.length === 0 && !searching" class="text-center py-4 text-slate-400 text-xs italic">
        Aucun résultat pour cette recherche.
    </div>
</div>
