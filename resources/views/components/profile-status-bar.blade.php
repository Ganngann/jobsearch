@php
    $user = Auth::user();
    if (!$user) return;

    // 1. Narrative Logic (70/30)
    $factsCount = $user->facts()->count();
    $journeyCount = $user->experiences()->count() + $user->educations()->count();
    
    $narrativeScore = min(70, ($factsCount / 20) * 70);
    $journeyScore = min(30, ($journeyCount / 3) * 30);
    $narrativeProgress = round($narrativeScore + $journeyScore);

    // 2. Skills Logic (Target 50)
    $skillsCount = $user->skills()->count();
    $skillsProgress = min(100, round(($skillsCount / 50) * 100));

    // 3. ROME Logic (Target 3)
    $romeCount = $user->preferredMetiers()->count();
    $romeProgress = min(100, round(($romeCount / 3) * 100));

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

<div class="flex items-center gap-3" x-data="{ 
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
    }
}"
@skill-added.window="skillsCount++"
@skill-removed.window="skillsCount--"
@fact-added.window="factsCount++"
@metier-added.window="romeCount++"
@metier-removed.window="romeCount--"
>
    <!-- NARRATIVE PROGRESS -->
    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Aperçu du CV</span>
    <div class="h-4 w-px bg-gray-200"></div>
    <div class="flex items-center gap-2 group relative">
        <div class="w-24 h-1.5 bg-gray-200 rounded-full overflow-hidden">
            <div class="bg-indigo-600 h-full transition-all duration-1000" :style="`width: ${narrativeProgress}%`"></div>
        </div>
        <span class="text-[10px] font-black text-indigo-600" x-text="narrativeProgress + '%'"></span>
        
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
                    <a href="{{ route('profile.skills.index') }}" class="bg-white text-indigo-600 px-2 py-0.5 rounded-md hover:bg-indigo-50 transition-colors">
                        Passer aux Compétences →
                    </a>
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

    <div class="h-4 w-px bg-gray-200 mx-2"></div>

    <!-- SKILLS PROGRESS -->
    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Compétences</span>
    <div class="h-4 w-px bg-gray-200 mx-1"></div>
    <div class="flex items-center gap-2 group relative">
        <div class="w-24 h-1.5 bg-gray-200 rounded-full overflow-hidden">
            <div class="bg-violet-500 h-full transition-all duration-1000" :style="`width: ${skillsProgress}%`"></div>
        </div>
        <span class="text-[10px] font-black text-violet-500" x-text="skillsProgress + '%'"></span>
        
        <!-- 100% SKILLS SUCCESS BUBBLE (Hide if ROME is also 100% or if already on discovery page) -->
        <template x-if="skillsProgress >= 100 && romeProgress < 100 && '{{ Route::currentRouteName() }}' !== 'discovery.index'">
            <div x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-90"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 class="absolute -bottom-12 left-0 z-[110] whitespace-nowrap">
                <div class="bg-violet-600 text-white text-[10px] font-black px-3 py-1.5 rounded-full shadow-xl shadow-violet-200 flex items-center gap-2 border border-violet-500">
                    <span class="flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-white opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                    </span>
                    Compétences OK !
                    <a href="{{ route('discovery.index') }}" class="bg-white text-violet-600 px-2 py-0.5 rounded-md hover:bg-violet-50 transition-colors">
                        Choisir mes métiers ROME →
                    </a>
                </div>
                <div class="absolute -top-1 left-6 w-2 h-2 bg-violet-600 rotate-45 border-l border-t border-violet-500"></div>
            </div>
        </template>

        <div class="cursor-help text-gray-300 hover:text-violet-400 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <div class="absolute top-full left-1/2 -translate-x-1/2 mt-3 w-72 bg-white border border-gray-100 rounded-2xl shadow-2xl p-5 z-[100] invisible group-hover:visible opacity-0 group-hover:opacity-100 transition-all transform translate-y-1 group-hover:translate-y-0">
            <div class="absolute -top-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-white border-l border-t border-gray-100 rotate-45"></div>
            <div class="relative space-y-4">
                <h4 class="text-[10px] font-black text-violet-600 uppercase tracking-widest text-center">Checklist des Compétences</h4>
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-[10px]">
                        <span class="text-gray-400 font-medium">Compétences identifiées</span>
                        <span class="font-black text-violet-600"><span x-text="skillsCount"></span> / 50</span>
                    </div>
                </div>
                <div class="pt-3 border-t border-gray-50 text-[9px] text-gray-400 italic text-center">
                    Rendez-vous dans l'Atelier pour valider ou écarter les suggestions de l'IA.
                </div>
            </div>
        </div>
    </div>

    <div class="h-4 w-px bg-gray-200 mx-2"></div>

    <!-- ROME PROGRESS -->
    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Ciblage ROME</span>
    <div class="h-4 w-px bg-gray-200 mx-1"></div>
    <div class="flex items-center gap-2 group relative">
        
        <div class="w-24 h-1.5 bg-gray-200 rounded-full overflow-hidden">
            <div class="bg-rose-500 h-full transition-all duration-1000" :style="`width: ${romeProgress}%`"></div>
        </div>
        <span class="text-[10px] font-black text-rose-500" x-text="romeProgress + '%'"></span>

        <!-- 100% ROME SUCCESS BUBBLE -->
        <template x-if="romeProgress >= 100 && '{{ Route::currentRouteName() }}' !== 'dashboard'">
            <div x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-90"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 class="absolute -bottom-12 left-0 z-[110] whitespace-nowrap">
                <div class="bg-rose-600 text-white text-[10px] font-black px-3 py-1.5 rounded-full shadow-xl shadow-rose-200 flex items-center gap-2 border border-rose-500">
                    <span class="flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-white opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                    </span>
                    Ciblage parfait !
                    <a href="{{ route('dashboard') }}" class="bg-white text-rose-600 px-2 py-0.5 rounded-md hover:bg-rose-50 transition-colors">
                        Voir mes offres matchées →
                    </a>
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
</div>
