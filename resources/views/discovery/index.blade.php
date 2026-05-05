<x-app-layout>
    <div class="py-12 bg-slate-50 min-h-screen" x-data="discoveryApp({{ $initialSuggestions->toJson() }}, {{ $savedMetiers->toJson() }})">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <x-discovery.header>
                Laissez l'IA explorer le marché de l'emploi pour dénicher les métiers qui résonnent avec votre personnalité profonde.
            </x-discovery.header>

            <x-discovery.error-message />

            <x-discovery.magic-button />

            <x-discovery.loading-state />

            <!-- Suggestions Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8" x-show="suggestions.length > 0">
                <template x-for="s in suggestions" :key="s.code">
                    <x-discovery.suggestion-card />
                </template>
            </div>

            <x-discovery.empty-state>
                <p>Cliquez sur le bouton ci-dessus pour lancer votre exploration.</p>
            </x-discovery.empty-state>

            <!-- Mes Métiers Cibles & Recherche -->
            <div class="mt-16 mb-12 grid grid-cols-1 lg:grid-cols-3 gap-8 border-t border-slate-200 pt-16">
                <x-discovery.search-manual />
                <x-discovery.saved-list />
            </div>

        </div>
    </div>

    <script>
        function discoveryApp(initialSuggestions = [], savedMetiers = []) {
            return {
                loading: false,
                errorMessage: '',
                suggestions: initialSuggestions,
                savedMetiers: savedMetiers,
                searchQuery: '',
                searchResults: [],
                searching: false,
                loadingMessage: '',
                messages: [
                    "Analyse de votre profil narratif...",
                    "Exploration des 532 fiches ROME...",
                    "Détection des talents cachés...",
                    "Calcul des ponts sémantiques...",
                    "Finalisation des propositions personnalisées..."
                ],
                messageInterval: null,

                async getSuggestions() {
                    this.loading = true;
                    this.errorMessage = '';
                    this.suggestions = [];
                    
                    // Rotation des messages de chargement
                    let msgIndex = 0;
                    this.loadingMessage = this.messages[0];
                    this.messageInterval = setInterval(() => {
                        msgIndex = (msgIndex + 1) % this.messages.length;
                        this.loadingMessage = this.messages[msgIndex];
                    }, 2500);

                    try {
                        const res = await fetch('{{ route('discovery.suggest') }}');
                        const data = await res.json();
                        
                        if (data.status === 'error') {
                            this.errorMessage = data.message;
                        } else {
                            this.suggestions = data.suggestions;
                        }
                    } catch (e) {
                        this.errorMessage = "Une erreur réseau est survenue. Vérifiez votre connexion.";
                        console.error(e);
                    } finally {
                        clearInterval(this.messageInterval);
                        this.loading = false;
                    }
                },

                async searchMetiers() {
                    if (this.searchQuery.length < 2) {
                        this.searchResults = [];
                        return;
                    }
                    this.searching = true;
                    try {
                        const res = await fetch(`/api/metiers/search?q=${encodeURIComponent(this.searchQuery)}`);
                        this.searchResults = await res.json();
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.searching = false;
                    }
                },

                async setMetierStatus(v, status) {
                    const oldStatus = v.status;
                    const newStatus = v.status === status ? 'none' : status;
                    
                    try {
                        const res = await fetch(`/discovery/metiers/${v.id}/status`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ status: newStatus })
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            v.status = data.current_status;
                            
                            // Gérer la liste des enregistrements
                            if (v.status === 'favorite') {
                                if (!this.savedMetiers.find(m => m.id === v.id && m.type === 'specific')) {
                                    this.savedMetiers = [...this.savedMetiers, {
                                        id: v.id,
                                        code: v.code,
                                        title: v.label || v.title,
                                        type: 'specific'
                                    }];
                                }
                                window.dispatchEvent(new CustomEvent('metier-added'));
                            } else if (oldStatus === 'favorite' && v.status !== 'favorite') {
                                this.savedMetiers = this.savedMetiers.filter(m => !(m.id === v.id && m.type === 'specific'));
                                window.dispatchEvent(new CustomEvent('metier-removed'));
                            }
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                async setReferentielStatus(s, status) {
                    const oldStatus = s.status;
                    const newStatus = s.status === status ? 'none' : status;
                    
                    try {
                        const res = await fetch(`/discovery/referentiel/${s.code}/status`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ status: newStatus })
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            s.status = data.current_status;
                            
                            // Propager visuellement le statut aux variantes
                            if (s.variants) {
                                s.variants.forEach(v => {
                                    v.status = s.status;
                                });
                            }

                            // Gérer la liste des enregistrements
                            if (s.status === 'favorite') {
                                if (!this.savedMetiers.find(m => m.code === s.code && m.type === 'family')) {
                                    this.savedMetiers = [...this.savedMetiers, {
                                        id: s.id,
                                        code: s.code,
                                        title: s.title,
                                        type: 'family'
                                    }];
                                }
                                window.dispatchEvent(new CustomEvent('metier-added'));
                            } else if (oldStatus === 'favorite' && s.status !== 'favorite') {
                                this.savedMetiers = this.savedMetiers.filter(m => !(m.code === s.code && m.type === 'family'));
                                window.dispatchEvent(new CustomEvent('metier-removed'));
                            }
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                async removeFromSaved(item) {
                    const status = 'none';
                    const url = item.type === 'family' 
                        ? `/discovery/referentiel/${item.code}/status`
                        : `/discovery/metiers/${item.id}/status`;
                    
                    try {
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ status: status })
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            this.savedMetiers = this.savedMetiers.filter(m => m !== item);
                            
                            // Update suggestions status if present
                            if (item.type === 'family') {
                                const sugg = this.suggestions.find(s => s.code === item.code);
                                if (sugg) {
                                    sugg.status = 'none';
                                    if (sugg.variants) sugg.variants.forEach(v => v.status = 'none');
                                }
                            } else {
                                this.suggestions.forEach(s => {
                                    if (s.variants) {
                                        const v = s.variants.find(varItem => varItem.id === item.id);
                                        if (v) v.status = 'none';
                                    }
                                });
                            }
                            window.dispatchEvent(new CustomEvent('metier-removed'));
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                async toggleBlacklist(s) {
                    const isBlacklisting = !s.is_blacklisted;
                    try {
                        const res = await fetch(`/discovery/referentiel/${s.code}/status`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ status: isBlacklisting ? 'refused' : 'none' })
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            s.is_blacklisted = !s.is_blacklisted;
                            if (s.is_blacklisted) {
                                s.status = 'refused';
                                if (s.variants) {
                                    s.variants.forEach(v => v.status = 'refused');
                                }
                                // Si c'était un favori, on le retire des saved
                                this.savedMetiers = this.savedMetiers.filter(m => !(m.code === s.code && m.type === 'family'));
                            } else {
                                s.status = 'none';
                                if (s.variants) {
                                    s.variants.forEach(v => v.status = 'none');
                                }
                            }
                        }
                    } catch (e) {
                        console.error(e);
                    }
                }
            }
        }
    </script>
</x-app-layout>
