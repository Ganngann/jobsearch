<div>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Confiance Algo</h3>
        <span class="text-[10px] font-black {{ $user->profile_completion >= 70 ? 'text-emerald-500' : ($user->profile_completion >= 30 ? 'text-amber-500' : 'text-slate-400') }}">
            Niveau {{ $user->profile_completion >= 70 ? '3' : ($user->profile_completion >= 30 ? '2' : '1') }}
        </span>
    </div>
    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
        <div 
            class="h-full transition-all duration-1000 {{ $user->profile_completion >= 70 ? 'bg-emerald-500' : ($user->profile_completion >= 30 ? 'bg-amber-500' : 'bg-indigo-500') }}" 
            style="width: {{ $user->profile_completion }}%"
        ></div>
    </div>
    <p class="mt-2 text-[9px] font-bold text-slate-400">
        @if($user->profile_completion >= 70)
            Précision optimale : Tous les critères de friction et sémantiques sont actifs.
        @elseif($user->profile_completion >= 30)
            Précision intermédiaire : Filtrage basé sur les compétences et la mobilité uniquement.
        @else
            Précision limitée : Matching basé principalement sur les intitulés de métiers.
        @endif
    </p>
</div>
