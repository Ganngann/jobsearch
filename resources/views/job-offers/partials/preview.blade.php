<div class="h-full flex flex-col bg-white overflow-hidden">
    <!-- Header -->
    <div class="p-8 border-b border-slate-100 bg-slate-50/50">
        <div class="flex items-start justify-between gap-6">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-3">
                    <span class="px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 text-[10px] font-black uppercase tracking-widest">
                        {{ $jobOffer->metier->label ?? 'Métier non spécifié' }}
                    </span>
                    <span class="px-3 py-1 rounded-full bg-slate-200 text-slate-600 text-[10px] font-black uppercase tracking-widest">
                        {{ $jobOffer->forem_ref }}
                    </span>
                </div>
                <h2 class="text-3xl font-black text-slate-900 leading-tight">
                    {{ $jobOffer->title }}
                </h2>
                <div class="mt-4 flex items-center gap-6">
                    <div class="flex items-center gap-2 text-slate-500">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-7h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <span class="text-sm font-bold">{{ $jobOffer->employer->label }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-slate-500">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span class="text-sm font-bold">{{ $jobOffer->location }}</span>
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col items-end gap-4">
                <div class="flex items-center gap-4">
                    <!-- Score Data -->
                    <div class="text-center p-4 bg-white rounded-2xl border border-slate-100 shadow-sm min-w-[100px]">
                        <p class="text-3xl font-black {{ $match->pre_score >= 70 ? 'text-emerald-500' : ($match->pre_score >= 40 ? 'text-amber-500' : 'text-slate-400') }}">
                            {{ $match->pre_score }}<span class="text-xs">%</span>
                        </p>
                        <p class="text-[8px] font-black uppercase tracking-widest text-slate-400 mt-1">Data Match</p>
                    </div>

                    @if($match->final_score !== null)
                        <!-- Score IA -->
                        <div class="text-center p-4 bg-indigo-600 rounded-2xl shadow-lg shadow-indigo-100 min-w-[100px]">
                            <p class="text-3xl font-black text-white">
                                {{ $match->final_score }}<span class="text-xs">%</span>
                            </p>
                            <p class="text-[8px] font-black uppercase tracking-widest text-indigo-200 mt-1">IA Match</p>
                        </div>
                    @endif
                </div>

                @if($match->final_score === null)
                    <form action="{{ route('jobs.match', $jobOffer) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full py-3 px-6 bg-indigo-600 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                            Lancer Analyse IA
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto p-8 space-y-10 custom-scrollbar">
        @if($match->final_score !== null)
            <!-- IA Narrative -->
            <div class="p-6 bg-indigo-50 border border-indigo-100 rounded-3xl relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <svg class="w-20 h-20 text-indigo-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71z"></path></svg>
                </div>
                <h3 class="text-sm font-black text-indigo-900 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <span class="w-2 h-2 bg-indigo-500 rounded-full animate-pulse"></span>
                    Analyse de l'IA
                </h3>
                <p class="text-indigo-800 leading-relaxed font-medium">
                    {{ $match->ai_analysis_narrative }}
                </p>
                <div class="mt-6 pt-6 border-t border-indigo-200/50">
                    <h4 class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-3">Conseil de l'IA</h4>
                    <p class="text-sm text-indigo-900 italic font-bold">"{{ $match->ai_recommendation }}"</p>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-2 gap-8">
            <!-- Details Section -->
            <div class="space-y-8">
                <div>
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Description du poste</h3>
                    <div class="prose prose-slate max-w-none text-slate-600 text-sm leading-relaxed">
                        {!! $jobOffer->description !!}
                    </div>
                </div>
            </div>

            <!-- Profile Match Section -->
            <div class="space-y-8">
                <div>
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Analyse des compétences</h3>
                    <div class="space-y-3">
                        @foreach($jobOffer->skills as $skill)
                            @php
                                $hasSkill = $user->skills->contains($skill->id);
                            @endphp
                            <div class="flex items-center justify-between p-3 rounded-xl border {{ $hasSkill ? 'bg-emerald-50 border-emerald-100 text-emerald-700' : 'bg-slate-50 border-slate-100 text-slate-400' }}">
                                <span class="text-xs font-bold">{{ $skill->label }}</span>
                                @if($hasSkill)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                @else
                                    <span class="text-[10px] font-black uppercase">Manquant</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Prérequis & Permis</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($jobOffer->permits as $permit)
                            @php $hasPermit = $user->permits->contains($permit->id); @endphp
                            <span class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase border {{ $hasPermit ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700' }}">
                                Permis {{ $permit->label }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="p-6 border-t border-slate-100 bg-white flex items-center justify-between">
        <a href="{{ route('jobs.show', $jobOffer) }}" class="text-indigo-600 text-sm font-black uppercase tracking-widest hover:text-indigo-800 transition-colors">
            Voir la fiche complète
        </a>
        <div class="flex items-center gap-4">
            <button class="p-3 rounded-xl border border-slate-200 hover:bg-slate-50 transition-all text-slate-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
            </button>
            <a href="{{ $jobOffer->apply_url ?? '#' }}" target="_blank" class="px-8 py-3 bg-slate-900 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-xl shadow-slate-200">
                Postuler
            </a>
        </div>
    </div>
</div>
