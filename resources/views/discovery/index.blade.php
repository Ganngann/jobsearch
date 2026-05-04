<x-app-layout>
    <div class="py-12 bg-slate-50 min-h-screen" x-data="discoveryApp({{ $initialSuggestions->toJson() }}, {{ Auth::user()->preferredMetiers()->pluck('metiers.id')->toJson() }})">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Header -->
            <div class="text-center mb-16">
                <h1 class="text-4xl font-black text-slate-900 mb-4">Le Miroir des Possibles</h1>
                <p class="text-xl text-slate-600 max-w-2xl mx-auto">
                    Laissez l'IA explorer le marché de l'emploi pour dénicher les métiers qui résonnent avec votre personnalité profonde.
                </p>
            </div>

            <!-- Error Message -->
            <div x-show="errorMessage" class="max-w-2xl mx-auto mb-8 p-4 bg-red-50 border border-red-100 text-red-600 rounded-2xl text-center font-medium">
                <p x-text="errorMessage"></p>
            </div>

            <!-- Magic Button -->
            <div class="flex justify-center mb-20">
                <button 
                    @click="getSuggestions()"
                    :disabled="loading"
                    class="relative group px-12 py-6 bg-gradient-to-r from-indigo-600 to-violet-600 rounded-full text-white font-black text-2xl shadow-2xl shadow-indigo-200 transition-all duration-300 hover:scale-105 active:scale-95 disabled:opacity-50 disabled:scale-100 overflow-hidden"
                >
                    <span class="relative z-10 flex items-center gap-3">
                        <template x-if="!loading">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </template>
                        <template x-if="loading">
                            <svg class="w-8 h-8 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </template>
                        <span x-text="loading ? 'Exploration en cours...' : (suggestions.length > 0 ? 'Nouvelle exploration' : 'Surprends-moi !')"></span>
                    </span>
                    <div class="absolute inset-0 bg-gradient-to-r from-violet-600 to-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </button>
            </div>

            <!-- Loading Messages -->
            <div x-show="loading" class="text-center mb-12 animate-pulse">
                <p class="text-indigo-600 font-medium text-lg" x-text="loadingMessage"></p>
            </div>

            <!-- Suggestions Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8" x-show="suggestions.length > 0">
                <template x-for="s in suggestions" :key="s.code">
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
                                    <div class="flex items-center justify-between gap-3 p-2 hover:bg-white rounded-xl transition-colors group/item">
                                        <span class="text-xs font-medium text-slate-600 line-clamp-2" x-text="v.label"></span>
                                        <div class="flex items-center gap-1 shrink-0">
                                            <!-- Favorite (+20) -->
                                            <button 
                                                @click="setMetierStatus(v, 'favorite')"
                                                class="p-1 rounded-md transition-all"
                                                :class="v.status === 'favorite' ? 'bg-red-50 text-red-500' : 'text-slate-300 hover:text-red-400'"
                                                title="Coup de cœur (+20 pts)"
                                            >
                                                <svg class="w-4 h-4" :fill="v.status === 'favorite' ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                            </button>

                                            <!-- Neutral (0) -->
                                            <button 
                                                @click="setMetierStatus(v, 'neutral')"
                                                class="p-1 rounded-md transition-all"
                                                :class="v.status === 'neutral' ? 'bg-slate-100 text-slate-500' : 'text-slate-300 hover:text-slate-500'"
                                                title="Neutre (0 pt)"
                                            >
                                                <svg class="w-4 h-4" :fill="v.status === 'neutral' ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            </button>
                                            
                                            <!-- Refused (-20) -->
                                            <button 
                                                @click="setMetierStatus(v, 'refused')"
                                                class="p-1 rounded-md transition-all"
                                                :class="v.status === 'refused' ? 'bg-black text-white' : 'text-slate-200 hover:text-slate-800'"
                                                title="Pas pour moi (-20 pts)"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            </button>
                                        </div>
                                    </div>
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
                </template>
            </div>

            <!-- Empty State -->
            <div x-show="!loading && suggestions.length === 0" class="text-center text-slate-400 py-20">
                <p>Cliquez sur le bouton ci-dessus pour lancer votre exploration.</p>
            </div>

        </div>
    </div>

    <script>
        function discoveryApp(initialSuggestions = [], favoriteMetierIds = []) {
            return {
                loading: false,
                errorMessage: '',
                suggestions: initialSuggestions,
                favoriteMetierIds: favoriteMetierIds,
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
                            
                            // Gérer la barre de progression (uniquement pour les favoris)
                            if (oldStatus !== 'favorite' && v.status === 'favorite') {
                                window.dispatchEvent(new CustomEvent('metier-added'));
                            } else if (oldStatus === 'favorite' && v.status !== 'favorite') {
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

                            // Gérer la barre de progression (uniquement pour les favoris)
                            if (oldStatus !== 'favorite' && s.status === 'favorite') {
                                window.dispatchEvent(new CustomEvent('metier-added'));
                            } else if (oldStatus === 'favorite' && s.status !== 'favorite') {
                                window.dispatchEvent(new CustomEvent('metier-removed'));
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
