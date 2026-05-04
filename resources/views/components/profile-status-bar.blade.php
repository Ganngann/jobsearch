@php
    $user = Auth::user();
    if (!$user) return;

    // 1. Narrative Logic (70/30)
    $factsCount = $user->facts()->count();
    $journeyCount = $user->experiences()->count() + $user->educations()->count();
    
    $narrativeScore = min(70, ($factsCount / 20) * 70);
    $journeyScore = min(30, ($journeyCount / 3) * 30);
    $narrativeProgress = round($narrativeScore + $journeyScore);

    // 2. Skills Logic (Target 50 mastered)
    $skillsCount = $user->skills()->wherePivot('status', 'active')->count();
    $skillsProgress = min(100, round(($skillsCount / 50) * 100));

    // 3. ROME Logic (Target 3 favorites: specific OR family)
    $specificCount = $user->preferredMetiers()->wherePivot('status', 'favorite')->count();
    $familyCount = $user->preferredReferentielMetiers()->wherePivot('status', 'favorite')->count();
    $romeCount = $specificCount + $familyCount;
    $romeProgress = min(100, round(($romeCount / 3) * 100));

    // 4. Mobility Logic
    $permitsCount = $user->permits()->count();
    $mobilityProgress = $user->zip_code ? 100 : 0;

    // Categories for Tooltip (Narrative)
    $categoryCounts = [
        'VALEURS' => $user->facts()->where('category', 'VALEURS')->count(),
        'OBJECTIFS' => $user->facts()->where('category', 'OBJECTIFS')->count(),
        'SOFT_SKILLS' => $user->facts()->where('category', 'SOFT_SKILLS')->count(),
        'PREFERENCES' => $user->facts()->where('category', 'PREFERENCES')->count(),
    ];
    
    $categories = [
        'VALEURS' => ['current' => $categoryCounts['VALEURS'], 'target' => 5],
        'OBJECTIFS' => ['current' => $categoryCounts['OBJECTIFS'], 'target' => 5],
        'SOFT_SKILLS' => ['current' => $categoryCounts['SOFT_SKILLS'], 'target' => 5],
        'PREFERENCES' => ['current' => $categoryCounts['PREFERENCES'], 'target' => 5],
    ];
@endphp

<div class="flex items-center gap-2" x-data="{ 
    skillsCount: {{ $skillsCount }},
    factsCount: {{ $factsCount }},
    journeyCount: {{ $journeyCount }},
    romeCount: {{ $romeCount }},
    get narrativeProgress() {
        let n = Math.min(70, (this.factsCount / 20) * 70);
        let j = Math.min(30, (this.journeyCount / 3) * 30);
        return Math.round(n + j);
    },
    get skillsProgress() {
        return Math.min(100, Math.round((this.skillsCount / 50) * 100));
    },
    get romeProgress() {
        return Math.min(100, Math.round((this.romeCount / 3) * 100));
    },
    mobilityProgress: {{ $mobilityProgress }},
    permitsCount: {{ $permitsCount }},
    hasSeenReadyBubble: localStorage.getItem('has_seen_ready_bubble') === 'true',
    dismissBubble() {
        this.hasSeenReadyBubble = true;
        localStorage.setItem('has_seen_ready_bubble', 'true');
    }
}"
@skill-added.window="skillsCount++"
@skill-removed.window="skillsCount--"
@fact-added.window="factsCount++"
@metier-added.window="romeCount++"
@metier-removed.window="romeCount--"
@mobility-updated.window="mobilityProgress = 100"
>
    <!-- NARRATIVE PROGRESS -->
    <a href="{{ route('profile.builder') }}" class="flex items-center gap-3 group/nav hover:bg-white/50 px-2 py-1 rounded-xl transition-all">
        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest group-hover/nav:text-indigo-600 transition-colors">Aperçu du CV</span>
        <div class="flex items-center gap-2 group relative">
            <div class="w-20 h-1.5 bg-gray-200 rounded-full overflow-hidden group-hover/nav:ring-4 group-hover/nav:ring-indigo-100 transition-all">
                <div class="bg-indigo-500 h-full transition-all duration-1000" :style="`width: ${narrativeProgress}%`"></div>
            </div>
            <span class="text-[10px] font-black text-indigo-500 w-8" x-text="narrativeProgress + '%'"></span>
        
            <!-- 100% SUCCESS BUBBLE (ONLY IF NOT ON SKILLS PAGE) -->
            <template x-if="narrativeProgress >= 100 && skillsProgress < 100 && '{{ Route::currentRouteName() }}' !== 'profile.skills.index'">
                <div x-transition:enter="transition ease-out duration-500"
                     x-transition:enter-start="opacity-0 translate-y-4 scale-90"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     class="absolute -bottom-12 left-0 z-[110] whitespace-nowrap">
                    <div class="bg-indigo-600 text-white text-[10px] font-black px-3 py-1.5 rounded-full shadow-xl shadow-indigo-200 flex items-center gap-2 border border-indigo-500">
                        <span class="flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-white opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                        </span>
                        Récit complet !
                        <span class="bg-white text-indigo-600 px-2 py-0.5 rounded-md">
                            Passer aux Compétences →
                        </span>
                    </div>
                    <div class="absolute -top-1 left-6 w-2 h-2 bg-indigo-600 rotate-45 border-l border-t border-indigo-500"></div>
                </div>
            </template>
            
            <div class="cursor-help text-gray-300 hover:text-indigo-400 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <div class="absolute top-full left-1/2 -translate-x-1/2 mt-3 w-72 bg-white border border-gray-100 rounded-2xl shadow-2xl p-5 z-[100] invisible group-hover:visible opacity-0 group-hover:opacity-100 transition-all transform translate-y-1 group-hover:translate-y-0">
                <div class="absolute -top-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-white border-l border-t border-gray-100 rotate-45"></div>
                <div class="relative space-y-4">
                    <h4 class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">Checklist de ton Profil</h4>
                    <div class="space-y-3">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[10px] font-bold text-gray-500 uppercase">Récit Narratif (70%)</span>
                            </div>
                            <div class="space-y-1.5 pl-2 border-l-2 border-indigo-50">
                                @foreach($categories as $cat => $data)
                                    <div class="flex items-center justify-between text-[10px]">
                                        <span class="text-gray-400 capitalize">{{ strtolower(str_replace('_', ' ', $cat)) }}</span>
                                        <span class="font-black {{ $data['current'] >= $data['target'] ? 'text-emerald-500' : 'text-indigo-600' }}">
                                            {{ $data['current'] }}/{{ $data['target'] }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="pt-2 border-t border-gray-50">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[10px] font-bold text-gray-500 uppercase">Parcours Pro (30%)</span>
                                <span class="text-[10px] font-black {{ $journeyCount >= 3 ? 'text-emerald-500' : 'text-indigo-600' }}">
                                    <span x-text="Math.min(3, journeyCount)"></span>/3
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-gray-50 text-[10px] text-gray-500 font-medium">
                        L'IA analyse vos réponses pour extraire ces faits. Un score de 80%+ est recommandé.
                    </div>
                </div>
            </div>
        </div>
    </a>

    <div class="h-4 w-px bg-gray-200 mx-1"></div>

    <!-- SKILLS PROGRESS -->
    <a href="{{ route('profile.skills.index') }}" class="flex items-center gap-3 group/nav hover:bg-white/50 px-2 py-1 rounded-xl transition-all">
        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest group-hover/nav:text-purple-600 transition-colors">Compétences</span>
        <div class="flex items-center gap-2 group relative">
            <div class="w-20 h-1.5 bg-gray-200 rounded-full overflow-hidden group-hover/nav:ring-4 group-hover/nav:ring-purple-100 transition-all">
                <div class="bg-purple-500 h-full transition-all duration-1000" :style="`width: ${skillsProgress}%`"></div>
            </div>
            <span class="text-[10px] font-black text-purple-500 w-8" x-text="skillsProgress + '%'"></span>
        
            <!-- 100% SKILLS SUCCESS BUBBLE -->
            <template x-if="skillsProgress >= 100 && romeProgress < 100 && '{{ Route::currentRouteName() }}' !== 'discovery.index'">
                <div x-transition:enter="transition ease-out duration-500"
                     x-transition:enter-start="opacity-0 translate-y-4 scale-90"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     class="absolute -bottom-12 left-0 z-[110] whitespace-nowrap">
                    <div class="bg-purple-600 text-white text-[10px] font-black px-3 py-1.5 rounded-full shadow-xl shadow-purple-200 flex items-center gap-2 border border-purple-500">
                        <span class="flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-white opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                        </span>
                        Compétences OK !
                        <span class="bg-white text-purple-600 px-2 py-0.5 rounded-md">
                            Choisir mes métiers ROME →
                        </span>
                    </div>
                    <div class="absolute -top-1 left-6 w-2 h-2 bg-purple-600 rotate-45 border-l border-t border-purple-500"></div>
                </div>
            </template>

            <div class="cursor-help text-gray-300 hover:text-purple-400 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <div class="absolute top-full left-1/2 -translate-x-1/2 mt-3 w-72 bg-white border border-gray-100 rounded-2xl shadow-2xl p-5 z-[100] invisible group-hover:visible opacity-0 group-hover:opacity-100 transition-all transform translate-y-1 group-hover:translate-y-0">
                <div class="absolute -top-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-white border-l border-t border-gray-100 rotate-45"></div>
                <div class="relative space-y-4">
                    <h4 class="text-[10px] font-black text-purple-600 uppercase tracking-widest text-center">Checklist des Compétences</h4>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-[10px]">
                            <span class="text-gray-400 font-medium">Compétences identifiées</span>
                            <span class="font-black text-purple-600"><span x-text="skillsCount"></span> / 50</span>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-gray-50 text-[9px] text-gray-400 italic text-center">
                        Rendez-vous dans l'Atelier pour valider ou écarter les suggestions de l'IA.
                    </div>
                </div>
            </div>
        </div>
    </a>

    <div class="h-4 w-px bg-gray-200 mx-1"></div>

    <!-- ROME PROGRESS -->
    <a href="{{ route('discovery.index') }}" class="flex items-center gap-3 group/nav hover:bg-white/50 px-2 py-1 rounded-xl transition-all">
        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest group-hover/nav:text-rose-600 transition-colors">Ciblage ROME</span>
        <div class="flex items-center gap-2 group relative">
            <div class="w-20 h-1.5 bg-gray-200 rounded-full overflow-hidden group-hover/nav:ring-4 group-hover/nav:ring-rose-100 transition-all">
                <div class="bg-rose-500 h-full transition-all duration-1000" :style="`width: ${romeProgress}%`"></div>
            </div>
            <span class="text-[10px] font-black text-rose-500 w-8" x-text="romeProgress + '%'"></span>

            <!-- 100% ROME SUCCESS BUBBLE -->
            <template x-if="romeProgress >= 100 && mobilityProgress < 100 && '{{ Route::currentRouteName() }}' !== 'profile.mobility.index'">
                <div x-transition:enter="transition ease-out duration-500"
                     x-transition:enter-start="opacity-0 translate-y-4 scale-90"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     class="absolute -bottom-12 left-0 z-[110] whitespace-nowrap">
                    <div class="bg-rose-600 text-white text-[10px] font-black px-3 py-1.5 rounded-full shadow-xl shadow-rose-200 flex items-center gap-2 border border-rose-500">
                        <span class="flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-white opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                        </span>
                        Ciblage OK !
                        <span class="bg-white text-rose-600 px-2 py-0.5 rounded-md">
                            Où cherchez-vous ? →
                        </span>
                    </div>
                    <div class="absolute -top-1 left-6 w-2 h-2 bg-rose-600 rotate-45 border-l border-t border-rose-500"></div>
                </div>
            </template>

            <div class="cursor-help text-gray-300 hover:text-rose-400 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <div class="absolute top-full left-1/2 -translate-x-1/2 mt-3 w-72 bg-white border border-gray-100 rounded-2xl shadow-2xl p-5 z-[100] invisible group-hover:visible opacity-0 group-hover:opacity-100 transition-all transform translate-y-1 group-hover:translate-y-0">
                <div class="absolute -top-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-white border-l border-t border-gray-100 rotate-45"></div>
                <div class="relative space-y-4">
                    <h4 class="text-[10px] font-black text-rose-600 uppercase tracking-widest text-center">Ciblage Métier</h4>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-[10px]">
                            <span class="text-gray-400 font-medium">Métiers sélectionnés</span>
                            <span class="font-black text-rose-600"><span x-text="romeCount"></span> / 3</span>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-gray-50 text-[9px] text-gray-400 italic text-center">
                        Sélectionnez au moins 3 métiers pour lesquels vous souhaitez recevoir des offres.
                    </div>
                </div>
            </div>
        </div>
    </a>

    <div class="h-4 w-px bg-gray-200 mx-1"></div>

    <!-- MOBILITY PROGRESS -->
    <a href="{{ route('profile.mobility.index') }}" class="flex items-center gap-3 group/nav hover:bg-white/50 px-2 py-1 rounded-xl transition-all">
        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest group-hover/nav:text-blue-600 transition-colors">Mobilité</span>
        <div class="flex items-center gap-2 group relative">
            <div class="w-20 h-1.5 bg-gray-200 rounded-full overflow-hidden group-hover/nav:ring-4 group-hover/nav:ring-blue-100 transition-all">
                <div class="bg-blue-500 h-full transition-all duration-1000" :style="`width: ${mobilityProgress}%`"></div>
            </div>
            <span class="text-[10px] font-black text-blue-500 w-8" x-text="mobilityProgress + '%'"></span>

            <!-- 100% MOBILITY SUCCESS BUBBLE -->
            <template x-if="mobilityProgress >= 100 && !hasSeenReadyBubble && '{{ Route::currentRouteName() }}' !== 'dashboard'">
                <div x-transition:enter="transition ease-out duration-500"
                     x-transition:enter-start="opacity-0 translate-y-4 scale-90"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     class="absolute -bottom-12 left-0 z-[110] whitespace-nowrap">
                    <a href="{{ route('dashboard') }}" 
                       @click="dismissBubble()"
                       class="bg-blue-600 text-white text-[10px] font-black px-3 py-1.5 rounded-full shadow-xl shadow-blue-200 flex items-center gap-2 border border-blue-500 hover:bg-blue-700 transition-colors">
                        <span class="flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-white opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                        </span>
                        Profil Prêt !
                        <span class="bg-white text-blue-600 px-2 py-0.5 rounded-md">
                            Voir mes offres matchées →
                        </span>
                    </a>
                    <div class="absolute -top-1 left-6 w-2 h-2 bg-blue-600 rotate-45 border-l border-t border-blue-500"></div>
                </div>
            </template>

            <div class="cursor-help text-gray-300 hover:text-blue-400 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <div class="absolute top-full left-1/2 -translate-x-1/2 mt-3 w-72 bg-white border border-gray-100 rounded-2xl shadow-2xl p-5 z-[100] invisible group-hover:visible opacity-0 group-hover:opacity-100 transition-all transform translate-y-1 group-hover:translate-y-0">
                <div class="absolute -top-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-white border-l border-t border-gray-100 rotate-45"></div>
                <div class="relative space-y-4">
                    <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-widest text-center">Rayon de Mobilité</h4>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-[10px]">
                            <span class="text-gray-400 font-medium">Lieu de vie configuré</span>
                            <span class="font-black text-blue-600" x-text="mobilityProgress >= 100 ? 'OUI' : 'NON'"></span>
                        </div>
                        <div class="flex items-center justify-between text-[10px]">
                            <span class="text-gray-400 font-medium">Permis de conduire</span>
                            <span class="font-black text-blue-600" x-text="permitsCount > 0 ? permitsCount + ' CONFIGURÉ(S)' : 'AUCUN'"></span>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-gray-50 text-[9px] text-gray-400 italic text-center">
                        Définissez votre point de départ et votre rayon de mobilité pour filtrer les offres locales.
                    </div>
                </div>
            </div>
        </div>
    </a>
</div>
