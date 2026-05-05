<x-app-layout>
    <div class="py-12 bg-slate-50" x-data="skillApp({
        activeSkills: {{ Js::from($activeSkills) }},
        neutralSkills: {{ Js::from($neutralSkills) }},
        refusedSkills: {{ Js::from($refusedSkills) }},
        csrfToken: '{{ csrf_token() }}',
        routes: {
            search: '{{ route('api.skills.search') }}',
            suggest: '{{ route('profile.skills.suggest') }}'
        }
    })">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Header -->
            <div class="mb-12">
                <h1 class="text-4xl font-black text-slate-900 mb-2">L'Atelier des Compétences</h1>
                <p class="text-lg text-slate-500 font-medium mb-8">Triez et qualifiez vos compétences pour un matching parfait.</p>

                <!-- Onboarding / Guide Section -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-indigo-600 rounded-3xl p-6 text-white shadow-xl shadow-indigo-100 flex flex-col justify-between overflow-hidden relative">
                        <div class="relative z-10">
                            <h3 class="text-lg font-black mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Impact sur le Score
                            </h3>
                            <p class="text-indigo-100 text-[11px] leading-relaxed font-medium">
                                Vos choix alimentent notre moteur de matching. Plus vous validez de compétences, plus vos scores de compatibilité avec les offres du Forem seront précis.
                            </p>
                        </div>
                        <div class="absolute -bottom-4 -right-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
                    </div>

                    <div class="md:col-span-2 bg-white rounded-3xl p-6 border border-slate-100 shadow-sm grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-slate-900 font-bold text-xs uppercase tracking-wider">
                                <div class="w-6 h-6 rounded-lg bg-slate-900 flex items-center justify-center text-[10px] text-white font-black">1</div>
                                Maîtrisé
                            </div>
                            <p class="text-[11px] text-slate-400 leading-tight">
                                <strong class="text-indigo-600 font-black">BONUS +</strong> : Accorde un bonus important si une offre demande cette compétence.
                            </p>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-slate-900 font-bold text-xs uppercase tracking-wider">
                                <div class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] text-slate-400 font-black">2</div>
                                Ignorer
                            </div>
                            <p class="text-[11px] text-slate-400 leading-tight">
                                <strong class="text-slate-600 font-black">NEUTRE</strong> : Pas d'impact. Utile pour les compétences "bonus" non stratégiques.
                            </p>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-slate-900 font-bold text-xs uppercase tracking-wider">
                                <div class="w-6 h-6 rounded-lg bg-rose-50 flex items-center justify-center text-[10px] text-rose-500 font-black">3</div>
                                Écarté
                            </div>
                            <p class="text-[11px] text-slate-400 leading-tight">
                                <strong class="text-rose-500 font-black">HANDICAP -</strong> : Applique une pénalité si l'offre exige absolument cette compétence.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search & Suggestion Engine -->
            <div class="mb-16">
                <div class="flex flex-col md:flex-row items-center justify-between gap-6 mb-8">
                    <h2 class="text-xl font-black text-slate-900 flex items-center gap-2">
                        <span class="w-2 h-8 bg-indigo-500 rounded-full"></span>
                        Ajouter des Compétences
                    </h2>
                    
                    <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto">
                        <!-- Search Bar -->
                        <div class="relative flex-1 sm:w-64">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input type="text" 
                                   x-model="search"
                                   @input.debounce.300ms="searchSkills()"
                                   placeholder="Rechercher une compétence..."
                                   class="block w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-2xl shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm font-medium">
                            
                            <!-- Search Results Dropdown -->
                            <div x-show="searchResults.length > 0" 
                                 @click.away="searchResults = []"
                                 class="absolute z-50 left-0 right-0 mt-2 bg-white border border-slate-200 rounded-2xl shadow-2xl overflow-hidden">
                                <template x-for="s in searchResults" :key="s.id">
                                    <button @click="addFromSearch(s)" 
                                            class="w-full text-left px-4 py-3 hover:bg-slate-50 transition-colors flex items-center justify-between group">
                                        <span class="text-sm font-bold text-slate-700" x-text="s.label"></span>
                                        <svg class="w-4 h-4 text-slate-300 group-hover:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Suggestion Button -->
                        <button 
                            @click="fetchSuggestions()"
                            :disabled="loading || suggestions.length > 0"
                            class="px-6 py-3 bg-indigo-600 text-white rounded-2xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 disabled:opacity-30 flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span x-text="suggestions.length > 0 ? 'Lot en cours...' : 'Suggérer des compétences'"></span>
                        </button>
                    </div>
                </div>

                <!-- Cards Grid (Suggestions) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <template x-for="(s, index) in suggestions" :key="s.id">
                        <div 
                            x-show="!s.hidden"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:leave="transition ease-in duration-300"
                            x-transition:leave-end="opacity-0 translate-y-8"
                            class="group relative bg-white rounded-3xl p-6 shadow-sm border border-slate-100 flex flex-col justify-between hover:shadow-2xl hover:shadow-indigo-100 hover:border-indigo-200 transition-all duration-500 overflow-hidden"
                        >
                            <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-50 rounded-full blur-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>

                            <div class="relative mb-6">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-50 text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-500 transition-colors" x-text="s.type || 'skill'"></span>
                                    <div class="text-[10px] font-bold text-slate-300" x-text="s.popularity + ' offres'"></div>
                                </div>
                                
                                <h3 class="text-xl font-bold text-slate-800 leading-tight mb-2 group-hover:text-indigo-600 transition-colors" x-text="s.label"></h3>
                                <p class="text-xs text-slate-400 font-medium italic leading-relaxed" x-text="s.reason"></p>
                            </div>

                            <div class="relative flex items-center gap-2">
                                <button @click="setStatus(s, 'active')" class="flex-[2] py-2.5 bg-slate-900 text-white rounded-xl font-bold text-xs hover:bg-indigo-600 transition-all shadow-md shadow-slate-100">
                                    Maîtrisé
                                </button>
                                <button @click="setStatus(s, 'neutral')" class="flex-1 py-2.5 bg-slate-50 text-slate-400 rounded-xl hover:bg-slate-200 hover:text-slate-600 transition-all flex items-center justify-center" title="Ignorer">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6"/></svg>
                                </button>
                                <button @click="setStatus(s, 'refused')" class="flex-1 py-2.5 bg-rose-50 text-rose-300 rounded-xl hover:bg-rose-600 hover:text-white transition-all flex items-center justify-center" title="Écarter">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Empty Placeholder -->
                    <template x-if="suggestions.length === 0 && !loading">
                        <div class="col-span-full py-12 bg-white rounded-3xl border-2 border-dashed border-slate-100 flex flex-col items-center justify-center text-center">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <p class="text-slate-400 font-medium">Besoin d'idées ? Lancez les suggestions de l'IA pour compléter votre profil.</p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Qualified Lists -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Validated -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
                    <h3 class="text-sm font-black text-indigo-600 uppercase tracking-widest mb-6 flex items-center justify-between">
                        Mes Compétences
                        <span class="bg-indigo-50 px-2 py-0.5 rounded text-[10px]" x-text="activeSkills.length"></span>
                    </h3>
                    <div class="space-y-2">
                        <template x-for="skill in activeSkills" :key="skill.id">
                            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl group hover:bg-white hover:shadow-sm transition-all border border-transparent hover:border-indigo-100">
                                <span class="text-sm font-bold text-slate-700" x-text="skill.label"></span>
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-all">
                                    <button @click="moveTo(skill, 'neutral')" class="p-1.5 text-slate-300 hover:text-slate-500" title="Ignorer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6"/></svg>
                                    </button>
                                    <button @click="moveTo(skill, 'refused')" class="p-1.5 text-slate-300 hover:text-rose-500" title="Écarter">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Neutral -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
                    <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-6 flex items-center justify-between">
                        Ignorées (Neutre)
                        <span class="bg-slate-50 px-2 py-0.5 rounded text-[10px]" x-text="neutralSkills.length"></span>
                    </h3>
                    <div class="space-y-2">
                        <template x-for="skill in neutralSkills" :key="skill.id">
                            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl group hover:bg-white hover:shadow-sm transition-all border border-transparent hover:border-slate-200">
                                <span class="text-sm font-medium text-slate-500" x-text="skill.label"></span>
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-all">
                                    <button @click="moveTo(skill, 'active')" class="p-1.5 text-indigo-400 hover:text-indigo-600" title="Maîtriser">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                    <button @click="moveTo(skill, 'refused')" class="p-1.5 text-slate-300 hover:text-rose-500" title="Écarter">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Refused -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
                    <h3 class="text-sm font-black text-rose-400 uppercase tracking-widest mb-6 flex items-center justify-between">
                        Écartées (Handicap)
                        <span class="bg-rose-50 px-2 py-0.5 rounded text-[10px]" x-text="refusedSkills.length"></span>
                    </h3>
                    <div class="space-y-2">
                        <template x-for="skill in refusedSkills" :key="skill.id">
                            <div class="flex items-center justify-between p-3 bg-rose-50/30 rounded-xl group hover:bg-white hover:shadow-sm transition-all border border-transparent hover:border-rose-100">
                                <span class="text-sm font-medium text-rose-900/60" x-text="skill.label"></span>
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-all">
                                    <button @click="moveTo(skill, 'active')" class="p-1.5 text-rose-300 hover:text-indigo-600" title="Maîtriser">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </button>
                                    <button @click="moveTo(skill, 'neutral')" class="p-1.5 text-rose-300 hover:text-slate-600" title="Ignorer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

        </div>
    </div>

</x-app-layout>
