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

    // 3. ROME Logic (TBD - Prototyping)
    $romeCount = 0; // À implémenter plus tard
    $romeProgress = 0;

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
    get narrativeProgress() {
        let n = Math.min(70, (this.factsCount / 20) * 70);
        let j = Math.min(30, (this.journeyCount / 3) * 30);
        return Math.round(n + j);
    },
    get skillsProgress() {
        return Math.min(100, Math.round((this.skillsCount / 50) * 100));
    }
}"
@skill-added.window="skillsCount++"
@skill-removed.window="skillsCount--"
@fact-added.window="factsCount++"
>
    <!-- NARRATIVE PROGRESS -->
    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Aperçu du CV</span>
    <div class="h-4 w-px bg-gray-200"></div>
    <div class="flex items-center gap-2 group relative">
        <div class="w-24 h-1.5 bg-gray-200 rounded-full overflow-hidden">
            <div class="bg-indigo-600 h-full transition-all duration-1000" :style="`width: ${narrativeProgress}%`"></div>
        </div>
        <span class="text-[10px] font-black text-indigo-600" x-text="narrativeProgress + '%'"></span>
        
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
</div>
