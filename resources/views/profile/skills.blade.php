<x-app-layout>
    <div class="py-12 bg-slate-50" x-data="skillApp()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Header -->
            <div class="mb-12">
                <h1 class="text-4xl font-black text-slate-900 mb-2">L'Atelier des Compétences</h1>
                <p class="text-lg text-slate-500 font-medium mb-8">Triez et qualifiez les compétences extraites de votre récit.</p>

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
                                Refuser
                            </div>
                            <p class="text-[11px] text-slate-400 leading-tight">
                                <strong class="text-rose-500 font-black">HANDICAP -</strong> : Applique une pénalité si l'offre exige absolument cette compétence.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Suggestion Engine -->
            <div class="mb-16">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-xl font-black text-slate-900 flex items-center gap-2">
                        <span class="w-2 h-8 bg-indigo-500 rounded-full"></span>
                        Suggestions de l'IA
                    </h2>
                    <button 
                        @click="fetchSuggestions()"
                        :disabled="loading || suggestions.length > 0"
                        class="px-6 py-3 bg-white border border-slate-200 rounded-2xl font-bold text-slate-600 hover:text-indigo-600 hover:border-indigo-200 transition-all shadow-sm disabled:opacity-30 flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span x-text="suggestions.length > 0 ? 'Lot en cours...' : 'Suggérer des compétences'"></span>
                    </button>
                </div>

                <!-- Cards Grid -->
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
                            <!-- Glow effect on hover -->
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
                                <button @click="setStatus(s, 'neutral')" class="flex-1 py-2.5 bg-slate-50 text-slate-400 rounded-xl hover:bg-slate-200 hover:text-slate-600 transition-all flex items-center justify-center" title="Plus tard">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6"/></svg>
                                </button>
                                <button @click="setStatus(s, 'refused')" class="flex-1 py-2.5 bg-rose-50 text-rose-300 rounded-xl hover:bg-rose-600 hover:text-white transition-all flex items-center justify-center" title="Pas pertinent">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Empty Placeholder -->
                    <template x-if="suggestions.length === 0 && !loading">
                        <div class="col-span-full py-12 bg-white rounded-3xl border-2 border-dashed border-slate-100 flex flex-col items-center justify-center text-center">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a2 2 0 00-1.96 1.414l-.703 2.108a2 2 0 01-.736.951l-1.918 1.279a2 2 0 01-1.108.343H11a2 2 0 01-2-2v-1a2 2 0 00-2-2H6a2 2 0 01-2-2v-.5a2 2 0 01.5-.5H5a2 2 0 002-2V8a2 2 0 012-2h.5a2 2 0 01.5.5V7a2 2 0 002 2h1a2 2 0 012 2v1.5a2 2 0 01.5.5H19a2 2 0 012 2v.5a2 2 0 01-.5.5z"/></svg>
                            </div>
                            <p class="text-slate-400 font-medium">Aucune suggestion active. Lancez l'IA pour commencer le tri.</p>
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
                            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl group">
                                <span class="text-sm font-bold text-slate-700" x-text="skill.label"></span>
                                <button @click="moveTo(skill, 'refused')" class="opacity-0 group-hover:opacity-100 text-slate-300 hover:text-rose-500 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Neutral -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
                    <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-6 flex items-center justify-between">
                        Potentiels / Adaptable
                        <span class="bg-slate-50 px-2 py-0.5 rounded text-[10px]" x-text="neutralSkills.length"></span>
                    </h3>
                    <div class="space-y-2">
                        <template x-for="skill in neutralSkills" :key="skill.id">
                            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl group">
                                <span class="text-sm font-medium text-slate-500" x-text="skill.label"></span>
                                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-all">
                                    <button @click="moveTo(skill, 'active')" class="text-indigo-400 hover:text-indigo-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Refused -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
                    <h3 class="text-sm font-black text-rose-400 uppercase tracking-widest mb-6 flex items-center justify-between">
                        Écartés (Handicap -5)
                        <span class="bg-rose-50 px-2 py-0.5 rounded text-[10px]" x-text="refusedSkills.length"></span>
                    </h3>
                    <div class="space-y-2">
                        <template x-for="skill in refusedSkills" :key="skill.id">
                            <div class="flex items-center justify-between p-3 bg-rose-50/30 rounded-xl group">
                                <span class="text-sm font-medium text-rose-900/60" x-text="skill.label"></span>
                                <button @click="moveTo(skill, 'active')" class="opacity-0 group-hover:opacity-100 text-rose-300 hover:text-indigo-600 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function skillApp() {
            return {
                loading: false,
                suggestions: [],
                activeSkills: @json($activeSkills),
                neutralSkills: @json($neutralSkills),
                refusedSkills: @json($refusedSkills),
                
                get totalQualified() {
                    return this.activeSkills.length + this.neutralSkills.length + this.refusedSkills.length;
                },
                
                get progress() {
                    return Math.min(100, (this.totalQualified / 50) * 100);
                },

                async fetchSuggestions() {
                    this.loading = true;
                    try {
                        const res = await fetch('{{ route('profile.skills.suggest') }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                        });
                        const data = await res.json();
                        this.suggestions = data.suggestions.map(s => ({ ...s, hidden: false }));
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.loading = false;
                    }
                },

                async setStatus(skill, status) {
                    try {
                        const res = await fetch(`/profile/skills/${skill.id}/status`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ status })
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            // Animation
                            skill.hidden = true;
                            setTimeout(() => {
                                this.suggestions = this.suggestions.filter(s => s.id !== skill.id);
                                this.updateLocalLists(skill, status);
                            }, 300);
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                async moveTo(skill, status) {
                    try {
                        const res = await fetch(`/profile/skills/${skill.id}/status`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ status })
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            // On détermine l'ancien statut avant de filtrer
                            const oldStatus = this.activeSkills.find(s => s.id === skill.id) ? 'active' : 
                                            (this.neutralSkills.find(s => s.id === skill.id) ? 'neutral' : 'refused');

                            // On retire de toutes les listes
                            this.activeSkills = this.activeSkills.filter(s => s.id !== skill.id);
                            this.neutralSkills = this.neutralSkills.filter(s => s.id !== skill.id);
                            this.refusedSkills = this.refusedSkills.filter(s => s.id !== skill.id);
                            
                            // On ajoute à la nouvelle
                            this.updateLocalLists(skill, status, oldStatus);
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                updateLocalLists(skill, status, oldStatus = null) {
                    // Si on était en 'active' et qu'on change, on décrémente
                    if (oldStatus === 'active' && status !== 'active') {
                        window.dispatchEvent(new CustomEvent('skill-removed'));
                    }
                    // Si on devient 'active', on incrémente
                    if (status === 'active' && oldStatus !== 'active') {
                        window.dispatchEvent(new CustomEvent('skill-added'));
                    }

                    if (status === 'active') this.activeSkills.push(skill);
                    if (status === 'neutral') this.neutralSkills.push(skill);
                    if (status === 'refused') this.refusedSkills.push(skill);
                }
            }
        }
    </script>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
    </style>
</x-app-layout>
