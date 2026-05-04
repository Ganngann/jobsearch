@php
    $user = auth()->user();
    if (!$user) return;

    // 1. Narrative Progress (30%)
    $factsCount = $user->facts()->count();
    $narrativeScore = min(30, ($factsCount / 20) * 30);

    // 2. Journey Progress (20%)
    $journeyCount = $user->experiences()->count() + $user->educations()->count();
    $journeyScore = min(20, ($journeyCount / 3) * 20);

    // 3. Skills Progress (50%)
    $skillsCount = $user->skills()->count(); // On compte tout ce qui est classé (active, neutral, refused)
    $skillsScore = min(50, ($skillsCount / 50) * 50);

    $totalProgress = round($narrativeScore + $journeyScore + $skillsScore);
    
    // Détermination de la marche à suivre
    $nextStep = "Compléter votre récit";
    $nextUrl = route('profile.builder');
    
    if ($factsCount >= 15 && $skillsCount < 30) {
        $nextStep = "Trier vos talents";
        $nextUrl = route('profile.skills.index');
    } elseif ($totalProgress >= 80) {
        $nextStep = "Explorer les métiers";
        $nextUrl = route('discovery.index');
    }

    $isComplete = $totalProgress >= 100;
@endphp

@if(!$isComplete)
<div class="bg-white border-b border-indigo-100 sticky top-16 z-40 shadow-sm overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4 flex-1">
                <div class="relative w-12 h-12 flex-shrink-0">
                    <svg class="w-full h-full transform -rotate-90">
                        <circle cx="24" cy="24" r="20" stroke="currentColor" stroke-width="4" fill="transparent" class="text-slate-100" />
                        <circle cx="24" cy="24" r="20" stroke="currentColor" stroke-width="4" fill="transparent" class="text-indigo-600 transition-all duration-1000" 
                                stroke-dasharray="125.6" :stroke-dashoffset="125.6 - (125.6 * {{ $totalProgress }} / 100)" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center text-[10px] font-black text-indigo-600">
                        {{ $totalProgress }}%
                    </div>
                </div>
                <div>
                    <h4 class="text-[11px] font-black text-slate-900 uppercase tracking-widest leading-none mb-1">Onboarding en cours</h4>
                    <p class="text-[13px] text-slate-500 font-medium">
                        Prochaine étape : 
                        <a href="{{ $nextUrl }}" class="text-indigo-600 font-bold hover:underline underline-offset-4 decoration-2">
                            {{ $nextStep }}
                        </a>
                    </p>
                </div>
            </div>

            <!-- Milestones -->
            <div class="flex items-center gap-6 md:gap-12">
                <div class="flex flex-col items-center">
                    <div class="w-2 h-2 rounded-full mb-1 {{ $narrativeScore >= 30 ? 'bg-indigo-600' : 'bg-slate-200' }}"></div>
                    <span class="text-[9px] font-bold {{ $narrativeScore >= 30 ? 'text-indigo-600' : 'text-slate-400' }} uppercase leading-none">Récit</span>
                    <span class="text-[8px] font-black text-slate-300">{{ round(($factsCount / 20) * 100) }}%</span>
                </div>
                <div class="w-8 h-px bg-slate-100"></div>
                <div class="flex flex-col items-center">
                    <div class="w-2 h-2 rounded-full mb-1 {{ $journeyScore >= 20 ? 'bg-indigo-600' : 'bg-slate-200' }}"></div>
                    <span class="text-[9px] font-bold {{ $journeyScore >= 20 ? 'text-indigo-600' : 'text-slate-400' }} uppercase leading-none">Parcours</span>
                    <span class="text-[8px] font-black text-slate-300">{{ round(($journeyCount / 3) * 100) }}%</span>
                </div>
                <div class="w-8 h-px bg-slate-100"></div>
                <div class="flex flex-col items-center">
                    <div class="w-2 h-2 rounded-full mb-1 {{ $skillsScore >= 50 ? 'bg-indigo-600' : 'bg-slate-200' }}"></div>
                    <span class="text-[9px] font-bold {{ $skillsScore >= 50 ? 'text-indigo-600' : 'text-slate-400' }} uppercase leading-none">Talents</span>
                    <span class="text-[8px] font-black text-slate-300">{{ round(($skillsCount / 50) * 100) }}%</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Thin progress line at the very bottom -->
    <div class="absolute bottom-0 left-0 h-0.5 bg-indigo-600 transition-all duration-1000" style="width: {{ $totalProgress }}%"></div>
</div>
@endif
