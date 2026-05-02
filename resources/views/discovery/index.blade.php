<x-app-layout>
    <div class="py-12 bg-slate-50 min-h-screen" x-data="discoveryApp({{ $initialSuggestions->toJson() }})">
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

                        <div class="flex flex-col gap-3">
                            <button 
                                @click="toggleFavorite(s)"
                                :class="s.is_favorite ? 'bg-indigo-50 text-indigo-600 border-indigo-200' : 'bg-slate-50 text-slate-600 border-slate-200'"
                                class="w-full py-4 rounded-2xl border font-bold transition-all duration-300 flex items-center justify-center gap-2 hover:shadow-md"
                            >
                                <svg class="w-5 h-5" :fill="s.is_favorite ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                <span x-text="s.is_favorite ? 'Famille en favoris' : 'Ajouter aux favoris'"></span>
                            </button>
                            
                            <a 
                                :href="'/dashboard?rome=' + s.code" 
                                class="w-full py-4 bg-slate-900 text-white rounded-2xl font-bold text-center transition-all duration-300 hover:bg-black flex items-center justify-center gap-2"
                            >
                                🔍 Voir les offres
                            </a>
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
        function discoveryApp(initialSuggestions = []) {
            return {
                loading: false,
                errorMessage: '',
                suggestions: initialSuggestions,
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

                async toggleFavorite(s) {
                    try {
                        const res = await fetch(`/discovery/favorite/${s.code}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            }
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            s.is_favorite = data.is_favorite;
                        }
                    } catch (e) {
                        console.error(e);
                    }
                }
            }
        }
    </script>
</x-app-layout>
