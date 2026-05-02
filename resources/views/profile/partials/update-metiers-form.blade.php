<section x-data="metierSelector()">
    <header>
        <h2 class="text-lg font-bold text-slate-900">
            {{ __('Métiers Préférés (ROME)') }}
        </h2>
        <p class="mt-1 text-sm text-slate-600 font-medium">
            {{ __('Ajoutez les métiers pour lesquels vous souhaitez recevoir des offres.') }}
        </p>
    </header>

    <div class="mt-8 space-y-8">
        <!-- Barre de recherche -->
        <div class="relative">
            <div class="relative group">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400 group-focus-within:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input 
                    type="text" 
                    x-model="search" 
                    @input.debounce.300ms="fetchResults()"
                    placeholder="Rechercher un métier (ex: Jardinier, Comptable...)"
                    class="block w-full pl-12 pr-4 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl focus:bg-white focus:border-indigo-600 focus:ring-0 transition-all text-slate-900 font-medium placeholder:text-slate-400"
                >
            </div>

            <!-- Résultats Autocomplete -->
            <div 
                x-show="search.length >= 2" 
                @click.away="results = []"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="absolute z-50 w-full mt-2 bg-white border border-slate-200 rounded-2xl shadow-2xl overflow-hidden"
            >
                <!-- Résultats trouvés -->
                <div class="max-h-80 overflow-y-auto custom-scrollbar">
                    <template x-for="item in results" :key="item.id">
                        <button 
                            @click="addMetier(item)"
                            class="w-full text-left px-6 py-4 hover:bg-slate-50 border-b border-slate-50 last:border-0 flex items-center justify-between group"
                        >
                            <div>
                                <span class="block font-black text-slate-900 group-hover:text-indigo-600 transition-colors" x-text="item.label"></span>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest" x-text="'Code ROME : ' + item.code"></span>
                            </div>
                            <svg class="w-5 h-5 text-slate-300 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </button>
                    </template>
                </div>

                <!-- État Vide -->
                <div x-show="results.length === 0 && search.length >= 2" class="p-8 text-center bg-slate-50/50">
                    <div class="w-12 h-12 bg-white rounded-2xl shadow-sm flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <p class="text-sm font-black text-slate-900">Aucun métier trouvé pour "<span x-text="search"></span>"</p>
                    <p class="mt-1 text-xs text-slate-500 font-medium">Note : Seuls les métiers ayant des offres actives en base sont listés ici.</p>
                </div>
            </div>
        </div>

        <!-- Métiers Sélectionnés (Chips) -->
        <div>
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Mes intérêts actuels</h3>
            <div class="flex flex-wrap gap-3">
                <template x-for="metier in preferredMetiers" :key="metier.id">
                    <div class="flex items-center gap-2 pl-4 pr-2 py-2 bg-indigo-50 border border-indigo-100 rounded-xl transition-all hover:border-indigo-200">
                        <div class="flex flex-col">
                            <span class="text-xs font-black text-indigo-900" x-text="metier.label"></span>
                        </div>
                        <button 
                            @click="removeMetier(metier.id)"
                            class="p-1 text-indigo-300 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                </template>
                
                <template x-if="preferredMetiers.length === 0">
                    <p class="text-sm text-slate-400 italic">Aucun métier sélectionné. Utilisez la barre de recherche ci-dessus.</p>
                </template>
            </div>
        </div>

        <!-- Liste Noire (Blacklist) -->
        <template x-if="blacklistedMetiers.length > 0">
            <div class="pt-8 border-t border-slate-100">
                <h3 class="text-xs font-black text-rose-400 uppercase tracking-widest mb-4">Métiers exclus (Liste noire)</h3>
                <div class="flex flex-wrap gap-3">
                    <template x-for="metier in blacklistedMetiers" :key="metier.id">
                        <div class="flex items-center gap-2 pl-4 pr-2 py-2 bg-rose-50 border border-rose-100 rounded-xl group transition-all hover:bg-rose-100/50">
                            <span class="text-xs font-bold text-rose-700" x-text="metier.label"></span>
                            <button 
                                @click="unblacklistMetier(metier.id)"
                                class="p-1 text-rose-300 hover:text-rose-600 rounded-lg transition-all"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <script>
        function metierSelector() {
            return {
                search: '',
                results: [],
                preferredMetiers: @json($user->preferredMetiers->map->only(['id', 'label', 'code'])),
                blacklistedMetiers: @json($user->blacklistedMetiers->map->only(['id', 'label', 'code'])),

                fetchResults() {
                    if (this.search.length < 2) {
                        this.results = [];
                        return;
                    }
                    fetch(`/api/metiers/search?q=${encodeURIComponent(this.search)}`)
                        .then(res => res.json())
                        .then(data => {
                            // On filtre pour ne pas montrer ceux déjà sélectionnés ou blacklistés
                            this.results = data.filter(item => 
                                !this.preferredMetiers.find(m => m.id === item.id) &&
                                !this.blacklistedMetiers.find(m => m.id === item.id)
                            );
                        });
                },

                addMetier(metier) {
                    fetch(`/profile/metiers/${metier.id}/add`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    }).then(() => {
                        this.preferredMetiers.push(metier);
                        this.results = [];
                        this.search = '';
                        // Optionnel : Notification ou flash message
                    });
                },

                removeMetier(id) {
                    fetch(`/profile/metiers/${id}/remove`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    }).then(() => {
                        const index = this.preferredMetiers.findIndex(m => m.id === id);
                        this.preferredMetiers.splice(index, 1);
                    });
                },

                unblacklistMetier(id) {
                    fetch(`/profile/metiers/${id}/blacklist`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    }).then(() => {
                        const index = this.blacklistedMetiers.findIndex(m => m.id === id);
                        this.blacklistedMetiers.splice(index, 1);
                    });
                }
            }
        }
    </script>
</section>
