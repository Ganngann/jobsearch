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

<section x-data='{
            search: "",
            isSaving: false,
            isSyncing: false,
            selectedSkills: @json($initialSkills),
            allAvailable: @json($availableSkills),

            get filteredAvailable() {
                const query = this.search.toLowerCase();
                return this.allAvailable.filter(skill => {
                    const matchesSearch = skill.label.toLowerCase().includes(query);
                    const notSelected = !this.selectedSkills.find(s => s.id === skill.id);
                    return matchesSearch && notSelected;
                });
            },

            async save() {
                this.isSaving = true;
                try {
                    const response = await fetch("{{ route("profile.skills.update") }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}",
                            "Accept": "application/json"
                        },
                        body: JSON.stringify({
                            _method: "PATCH",
                            skills: this.selectedSkills.map(s => s.id),
                            levels: this.selectedSkills.reduce((acc, s) => {
                                acc[s.id] = s.level;
                                return acc;
                            }, {})
                        })
                    });
                    if (!response.ok) throw new Error("Erreur de sauvegarde");
                } catch (e) {
                    console.error(e);
                } finally {
                    setTimeout(() => { this.isSaving = false; }, 500);
                }
            },

            addSkill(skill) {
                if (!this.selectedSkills.find(s => s.id === skill.id)) {
                    this.selectedSkills.push({
                        id: skill.id,
                        label: skill.label,
                        level: "beginner",
                        type: skill.type,
                        sources: []
                    });
                    this.save();
                }
            },

            removeSkill(id) {
                this.selectedSkills = this.selectedSkills.filter(s => s.id !== id);
                this.save();
            },

            async syncSkills() {
                if (this.isSyncing) return;
                this.isSyncing = true;
                try {
                    const response = await fetch("{{ route('profile.builder.sync-skills') }}", {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": "{{ csrf_token() }}",
                            "Accept": "application/json"
                        }
                    });
                    const data = await response.json();
                    if (data.success) {
                        alert(data.message);
                        window.location.reload();
                    }
                } catch (e) {
                    console.error(e);
                    alert("Erreur lors de la synchronisation");
                } finally {
                    this.isSyncing = false;
                }
            }
        }'>
    <header class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Compétences (Hard & Soft Skills)') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __("Sélectionnez vos compétences. L'IA utilisera ces informations pour le matching.") }}
            </p>
        </div>
        
        <button type="button" 
                @click="syncSkills()" 
                :disabled="isSyncing"
                class="flex-shrink-0 relative inline-flex items-center justify-center px-6 py-2.5 min-w-[200px] bg-gradient-to-r from-indigo-600 to-violet-600 border border-transparent rounded-xl font-bold text-[10px] text-white uppercase tracking-widest hover:from-indigo-700 hover:to-violet-700 shadow-md transition-all disabled:opacity-50 group">
            <div class="flex items-center gap-2">
                <svg x-show="!isSyncing" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                <svg x-show="isSyncing" class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="isSyncing ? 'Synchronisation...' : 'Synchroniser avec mes récits'"></span>
            </div>
        </button>
    </header>



    <form method="post" action="{{ route('profile.skills.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="space-y-4">

            
            {{-- Status d'enregistrement --}}
            <div class="flex justify-end h-4">
                <span x-show="isSaving" class="text-[10px] text-amber-600 animate-pulse font-bold uppercase tracking-widest">Enregistrement...</span>
                <span x-show="!isSaving" class="text-[10px] text-green-500 font-bold uppercase tracking-widest flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"></path></svg>
                    Synchronisé
                </span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                {{-- COLONNE GAUCHE : Compétences sélectionnées --}}
                <div class="lg:col-span-8">
                    <h3 class="text-sm font-bold text-gray-700 mb-4 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                        {{ __('Mes Compétences Sélectionnées') }}
                    </h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <template x-for="skill in selectedSkills" :key="skill.id">
                            <div class="flex flex-col p-3 bg-white border border-gray-100 rounded-2xl shadow-sm hover:shadow-md transition-all group">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-bold text-gray-800" x-text="skill.label"></span>
                                    <button type="button" @click="removeSkill(skill.id)" class="text-gray-400 hover:text-red-500 transition-colors p-1" title="Supprimer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[9px] uppercase font-black px-2 py-1 rounded-lg bg-gray-100 text-gray-500" x-text="skill.type"></span>
                                </div>

                                <!-- SOURCES (FACTS) -->
                                <template x-if="skill.sources && skill.sources.length > 0">
                                    <div class="mt-3 pt-2 border-t border-gray-50">
                                        <p class="text-[8px] font-bold text-gray-400 uppercase mb-1 tracking-tight">Source narratif :</p>
                                        <div class="space-y-1">
                                            <template x-for="source in skill.sources">
                                                <p class="text-[10px] text-gray-500 leading-snug italic line-clamp-2" x-text="'« ' + source + ' »'"></p>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="selectedSkills.length === 0">
                            <div class="col-span-full py-12 text-center bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200">
                                <p class="text-sm text-gray-400 italic">{{ __('Aucune compétence encore ajoutée.') }}</p>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- COLONNE DROITE : Barre latérale de recherche --}}
                <div class="lg:col-span-4">
                    <div class="sticky top-6 space-y-4">
                        <div class="bg-gray-50 p-5 rounded-3xl border border-gray-100 shadow-inner">
                            <h3 class="text-sm font-bold text-gray-700 mb-4">{{ __('Ajouter une compétence') }}</h3>
                            
                            <div class="relative mb-4">
                                <x-text-input 
                                    x-model="search" 
                                    type="text" 
                                    placeholder="Chercher (PHP, Gestion...)" 
                                    class="w-full pl-10 bg-white border-none shadow-sm focus:ring-2 focus:ring-indigo-500"
                                    autocomplete="off"
                                />
                                <div class="absolute left-3 top-2.5 text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                            </div>

                            {{-- Liste des suggestions --}}
                            <div class="space-y-2 overflow-y-auto pr-1 custom-scrollbar" style="max-height: 400px;">
                                <template x-for="skill in filteredAvailable" :key="skill.id">
                                    <div 
                                        @click="addSkill(skill)"
                                        class="flex items-center justify-between p-3 bg-white hover:bg-indigo-600 hover:text-white rounded-2xl border border-gray-100 shadow-sm transition-all cursor-pointer group animate-fade-in"
                                    >
                                        <span class="text-xs font-bold" x-text="skill.label"></span>
                                        <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    </div>
                                </template>
                                <template x-if="filteredAvailable.length === 0 && search !== ''">
                                    <p class="text-xs text-gray-400 italic py-4 text-center">{{ __('Aucun résultat.') }}</p>
                                </template>
                                <template x-if="search === '' && filteredAvailable.length > 0">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold pt-2">{{ __('Suggestions') }}</p>
                                </template>
                            </div>
                        </div>
                        
                        <div class="p-4 bg-indigo-50 rounded-2xl border border-indigo-100">
                            <p class="text-[10px] text-indigo-700 leading-relaxed">
                                <strong>Conseil :</strong> Ajoutez à la fois vos compétences techniques et vos qualités humaines pour un meilleur matching IA.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</section>
