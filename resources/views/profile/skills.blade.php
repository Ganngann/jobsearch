<x-app-layout>
    <div class="py-12 bg-slate-50" x-data="skillApp({
        activeSkills: {{ Js::from($activeSkills) }},
        neutralSkills: {{ Js::from($neutralSkills) }},
        refusedSkills: {{ Js::from($refusedSkills) }},
        csrfToken: '{{ csrf_token() }}',
        routes: {
            search: '',
            suggest: '',
            soft: ''
        }
    })">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Header -->
            <div class="mb-12">
                <h1 class="text-4xl font-black text-slate-900 mb-2">Mes Savoir-être</h1>
                <p class="text-lg text-slate-500 font-medium mb-8">Gérez vos compétences comportementales et vos atouts personnels.</p>

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
                                <strong class="text-indigo-600 font-black">BONUS +{{ config('matching.bonuses.active_skill') }}%</strong> : Accorde un bonus de +{{ config('matching.bonuses.active_skill') }}% sur le score final pour chaque correspondance avec une offre.
                            </p>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-slate-900 font-bold text-xs uppercase tracking-wider">
                                <div class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] text-slate-400 font-black">2</div>
                                Ignorer
                            </div>
                            <p class="text-[11px] text-slate-400 leading-tight">
                                <strong class="text-slate-600 font-black">NEUTRE</strong> : Aucun impact sur le score. Utile pour les atouts secondaires ou non stratégiques.
                            </p>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-slate-900 font-bold text-xs uppercase tracking-wider">
                                <div class="w-6 h-6 rounded-lg bg-rose-50 flex items-center justify-center text-[10px] text-rose-500 font-black">3</div>
                                Écarté
                            </div>
                            <p class="text-[11px] text-slate-400 leading-tight">
                                <strong class="text-rose-500 font-black">MALUS -{{ config('matching.handicaps.refused_skill') }}%</strong> : Applique une pénalité de -{{ config('matching.handicaps.refused_skill') }}% sur le score si l'offre exige absolument cette compétence.
                            </p>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Qualified Lists -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Validated -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
                    <h3 class="text-sm font-black text-indigo-600 uppercase tracking-widest mb-6 flex items-center justify-between">
                        Mes Atouts personnels
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
