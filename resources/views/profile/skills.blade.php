<x-app-layout>
    <div class="py-12 bg-slate-50" x-data="skillApp()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Header & Progress -->
            <div class="mb-12">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
                        <div>
                            <h1 class="text-4xl font-black text-slate-900 mb-2">L'Atelier des Compétences</h1>
                            <p class="text-lg text-slate-500 font-medium">Triez et qualifiez les compétences extraites de votre récit.</p>
                        </div>
                    </div>

                    <!-- NEW PROGRESS RIBBON -->
                    <div class="bg-white px-6 py-4 rounded-3xl shadow-sm border border-slate-100 flex justify-center mb-8">
                        <x-profile-status-bar />
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
                            x-transition:enter-start="opacity-0 scale-90"
                            x-transition:leave="transition ease-in duration-300"
                            x-transition:leave-end="opacity-0 translate-y-12"
                            class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex flex-col justify-between group hover:shadow-xl hover:border-indigo-100 transition-all duration-300"
                        >
                            <div class="mb-4">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <span class="text-[10px] font-black uppercase tracking-tighter px-2 py-0.5 rounded bg-slate-50 text-slate-400" x-text="s.type || 'skill'"></span>
                                    <div class="flex -space-x-1">
                                        <template x-for="i in Math.min(3, Math.ceil(s.popularity / 50))">
                                            <div class="w-1.5 h-1.5 rounded-full bg-indigo-400"></div>
                                        </template>
                                    </div>
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 leading-tight group-hover:text-indigo-600 transition-colors" x-text="s.label"></h3>
                            </div>

                            <div class="flex items-center gap-2">
                                <button @click="setStatus(s, 'active')" class="flex-1 py-2 bg-indigo-50 text-indigo-600 rounded-xl font-bold text-sm hover:bg-indigo-600 hover:text-white transition-all">J'ai</button>
                                <button @click="setStatus(s, 'neutral')" class="p-2 bg-slate-50 text-slate-400 rounded-xl hover:bg-slate-200 hover:text-slate-600 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6"/></svg>
                                </button>
                                <button @click="setStatus(s, 'refused')" class="p-2 bg-rose-50 text-rose-400 rounded-xl hover:bg-rose-600 hover:text-white transition-all">
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
                            // On retire de toutes les listes
                            this.activeSkills = this.activeSkills.filter(s => s.id !== skill.id);
                            this.neutralSkills = this.neutralSkills.filter(s => s.id !== skill.id);
                            this.refusedSkills = this.refusedSkills.filter(s => s.id !== skill.id);
                            // On ajoute à la nouvelle
                            this.updateLocalLists(skill, status);
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                updateLocalLists(skill, status) {
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
