@php
    $initialSkills = $user->skills->map(function($s) use ($user) {
        return [
            'id' => $s->id,
            'label' => $s->label,
            'level' => $s->pivot->level ?? 'beginner',
            'type' => $s->type,
            'sources' => $s->userFacts()->where('user_id', $user->id)->pluck('content')->toArray()
        ];
    })->values();
    
    $availableSkills = $allSkills->map(function($s) {
        return [
            'id' => $s->id,
            'label' => $s->label,
            'type' => $s->type
        ];
    })->values();
@endphp

<section x-data="skillsManager({
    selectedSkills: @json($initialSkills),
    allAvailable: @json($availableSkills),
    blacklistedSkills: @json($user->blacklistedSkills->map(fn($s) => ['id' => $s->id, 'label' => $s->label])),
    csrfToken: '{{ csrf_token() }}',
    routes: {
        sync: '{{ route('profile.builder.sync-skills') }}',
        update: '{{ route('profile.skills.update') }}'
    }
})">
    <header class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-black text-gray-900 tracking-tight">
                {{ __('Compétences (Hard & Soft Skills)') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                {{ __("Sélectionnez vos compétences. L'IA utilisera ces informations pour le matching.") }}
            </p>
        </div>

        <button type="button" 
                @click="syncSkills()" 
                :disabled="isSyncing"
                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-indigo-600 to-violet-600 border border-transparent rounded-xl font-bold text-[11px] text-white uppercase tracking-widest hover:from-indigo-700 hover:to-violet-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all disabled:opacity-50 shadow-lg shadow-indigo-100">
            <svg x-show="!isSyncing" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
            <svg x-show="isSyncing" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span x-text="isSyncing ? 'Synchronisation...' : 'Synchroniser avec mes récits'"></span>
        </button>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        {{-- Liste des compétences sélectionnées --}}
        <div class="md:col-span-2 space-y-4">
            <h3 class="flex items-center text-sm font-bold text-gray-700 mb-4">
                <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Mes Compétences Sélectionnées
            </h3>

            <div class="grid grid-cols-1 gap-3">
                <template x-for="skill in selectedSkills" :key="skill.id">
                    <div x-data="{ showSources: false }"
                         @mouseenter="showSources = true"
                         @mouseleave="showSources = false"
                         class="bg-white border border-gray-100 p-4 rounded-2xl shadow-sm hover:shadow-md transition-all group relative">
                        {{-- Background accent --}}
                        <div class="absolute right-0 top-0 w-1 h-full rounded-r-2xl" :class="skill.type === 'soft' ? 'bg-amber-400' : 'bg-indigo-500'"></div>

                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-bold text-gray-900" x-text="skill.label"></span>
                                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full" 
                                          :class="skill.type === 'soft' ? 'bg-amber-50 text-amber-600' : 'bg-indigo-50 text-indigo-600'"
                                          x-text="skill.type === 'soft' ? 'Soft' : 'Hard'"></span>
                                </div>
                                
                                {{-- Bulle des Récits liés (Flottante au survol) --}}
                                <template x-if="skill.sources && skill.sources.length > 0">
                                    <div x-show="showSources" 
                                         x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                         class="absolute z-50 left-4 right-4 top-full -mt-2 p-4 bg-gray-900 text-white rounded-2xl shadow-2xl pointer-events-none border border-gray-800">
                                        
                                        {{-- Petite flèche --}}
                                        <div class="absolute -top-1 left-6 w-2 h-2 bg-gray-900 rotate-45 border-l border-t border-gray-800"></div>

                                        <p class="text-[9px] font-black text-indigo-400 uppercase tracking-wider mb-2">Preuve par l'expérience :</p>
                                        <div class="space-y-2">
                                            <template x-for="source in skill.sources">
                                                <p class="text-[10px] text-gray-300 italic leading-snug" x-text="'« ' + source + ' »'"></p>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="blacklist(skill)" class="p-2 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all" title="Blacklister">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                </button>
                                <button @click="removeSkill(skill.id)" class="p-2 text-gray-300 hover:text-amber-500 hover:bg-amber-50 rounded-xl transition-all" title="Retirer">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="selectedSkills.length === 0">
                    <div class="py-12 text-center bg-gray-50 rounded-3xl border-2 border-dashed border-gray-100">
                        <p class="text-gray-400 text-sm">Aucune compétence sélectionnée.</p>
                    </div>
                </template>
            </div>
        </div>

        {{-- Recherche et Ajout --}}
        <div class="space-y-6">
            <div class="bg-gray-50 p-6 rounded-3xl border border-gray-100">
                <h3 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-wider">Ajouter une compétence</h3>
                
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" 
                           x-model="search"
                           placeholder="Chercher (PHP, Gestion...)"
                           class="block w-full pl-11 pr-4 py-3 bg-white border-none rounded-2xl shadow-sm focus:ring-2 focus:ring-indigo-500 transition-all text-sm">
                </div>

                <div class="mt-4 space-y-1">
                    <template x-for="skill in filteredAvailable" :key="skill.id">
                        <button @click="addSkill(skill)" 
                                class="w-full text-left px-4 py-3 rounded-xl hover:bg-white hover:shadow-sm transition-all flex items-center justify-between group">
                            <span class="text-sm font-medium text-gray-700 group-hover:text-indigo-600" x-text="skill.label"></span>
                            <svg class="w-4 h-4 text-gray-300 group-hover:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </button>
                    </template>
                </div>

                <div x-show="search.length > 0 && search.length < 2" class="mt-4 text-xs text-gray-400 text-center">
                    Tapez au moins 2 caractères...
                </div>
            </div>

            <div class="bg-indigo-50 p-6 rounded-3xl">
                <p class="text-[11px] leading-relaxed text-indigo-700">
                    <span class="font-bold">Conseil :</span> Ajoutez à la fois vos compétences techniques et vos qualités humaines pour un meilleur matching IA.
                </p>
            </div>

            <div class="flex justify-end">
                <button type="button" @click="saveAll()" :disabled="isSaving"
                        class="w-full inline-flex justify-center items-center px-6 py-4 bg-gray-900 text-white rounded-2xl font-bold text-sm hover:bg-gray-800 transition-all disabled:opacity-50">
                    <span x-text="isSaving ? 'Enregistrement...' : 'Enregistrer les modifications'"></span>
                </button>
            </div>
        </div>
    </form>

    {{-- Section Blacklist --}}
    <div class="mt-12 pt-8 border-t border-gray-100" x-show="blacklistedSkills.length > 0">
        <h3 class="flex items-center text-sm font-bold text-gray-400 mb-6 uppercase tracking-widest">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
            Compétences exclues (Blacklist)
        </h3>

        <div class="flex flex-wrap gap-2">
            <template x-for="skill in blacklistedSkills" :key="skill.id">
                <div class="flex items-center gap-2 bg-gray-50 border border-gray-100 px-3 py-2 rounded-xl group hover:border-indigo-200 transition-all">
                    <span class="text-xs font-medium text-gray-500" x-text="skill.label"></span>
                    <button @click="unblacklist(skill)" 
                            class="p-1 text-gray-300 hover:text-indigo-600 transition-colors"
                            title="Restaurer cette compétence">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    </button>
                </div>
            </template>
        </div>
        <p class="mt-4 text-[10px] text-gray-400 italic">Ces compétences ne seront plus jamais suggérées par l'IA lors de vos prochaines analyses.</p>
    </div>


</section>
