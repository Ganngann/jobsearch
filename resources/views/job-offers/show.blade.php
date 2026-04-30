<x-app-layout>
    <div class="min-h-screen bg-[#f8fafc]">
        <!-- Header / Navigation contextuelle -->
        <div class="bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    <div class="flex items-center">
                        <a href="{{ route('dashboard') }}" class="group flex items-center text-slate-500 hover:text-indigo-600 transition-all duration-300">
                            <div class="p-2.5 rounded-2xl group-hover:bg-indigo-50 transition-colors duration-200 mr-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            </div>
                            <span class="font-bold text-sm tracking-tight">Retour aux opportunités</span>
                        </a>
                    </div>
                    <div class="hidden md:flex items-center gap-6">
                        <div class="flex flex-col items-end">
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Référence</span>
                            <span class="text-sm font-black text-slate-900">#{{ $jobOffer->forem_ref }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
                    
                    <!-- Colonne Principale -->
                    <div class="lg:col-span-8 space-y-8">
                        
                        <!-- Main Job Card -->
                        <div class="bg-white rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 overflow-hidden">
                            <div class="p-10">
                                <!-- Header Section -->
                                <div class="flex flex-col md:flex-row md:items-start justify-between gap-8 mb-12">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-6">
                                            <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-indigo-600 text-white shadow-lg shadow-indigo-100">Le Forem</span>
                                            <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-500">{{ $jobOffer->contract_type }}</span>
                                        </div>
                                        
                                        <h1 class="text-4xl font-black text-slate-900 leading-[1.1] tracking-tight mb-6">
                                            {{ $jobOffer->title }}
                                        </h1>

                                        <div class="flex flex-wrap items-center gap-y-4 gap-x-8">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-7h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                                </div>
                                                <span class="font-bold text-slate-700">{{ $jobOffer->employer->label }}</span>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                                </div>
                                                <span class="font-semibold text-slate-500">{{ $jobOffer->location }}</span>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                </div>
                                                <span class="font-semibold text-slate-500">{{ $jobOffer->start_date ? $jobOffer->start_date->format('d/m/Y') : 'Dès que possible' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="shrink-0 pt-2">
                                        @if($jobOffer->employer->logo_base64 && strlen($jobOffer->employer->logo_base64) > 100)
                                            <div class="w-32 h-32 p-6 bg-slate-50 rounded-[2rem] border border-slate-100 shadow-inner flex items-center justify-center">
                                                <img src="data:{{ $jobOffer->employer->logo_mime_type }};base64,{{ $jobOffer->employer->logo_base64 }}" class="w-full h-full object-contain" alt="Logo">
                                            </div>
                                        @else
                                            <div class="w-32 h-32 rounded-[2rem] bg-gradient-to-br from-indigo-500 to-violet-600 flex flex-col items-center justify-center text-white shadow-2xl shadow-indigo-200">
                                                <span class="text-4xl font-black">{{ substr($jobOffer->employer->label, 0, 1) }}</span>
                                                <span class="text-[10px] font-black uppercase tracking-widest mt-1 opacity-60">Employer</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Stats Grid -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 p-8 bg-slate-50 rounded-[2rem] border border-slate-100 mb-12">
                                    <div class="space-y-1">
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Régime</p>
                                        <p class="text-base font-black text-slate-800">{{ $jobOffer->working_regime }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Type</p>
                                        <p class="text-base font-black text-slate-800">{{ $jobOffer->working_regime_detail ?? 'Standard' }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Volume</p>
                                        <p class="text-base font-black text-slate-800">{{ $jobOffer->working_hours ?? '38' }}h/sem</p>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Recrutement</p>
                                        <p class="text-base font-black text-slate-800">{{ $jobOffer->nombre_postes }} poste{{ $jobOffer->nombre_postes > 1 ? 's' : '' }}</p>
                                    </div>
                                </div>

                                <!-- Job Content -->
                                <div class="space-y-8">
                                    <h3 class="text-2xl font-black text-slate-900 flex items-center gap-4">
                                        <span class="w-2 h-8 bg-indigo-600 rounded-full"></span>
                                        Mission & Responsabilités
                                    </h3>
                                    <div class="text-slate-600 leading-relaxed text-lg border-l-4 border-slate-100 pl-8 py-2">
                                        {!! $jobOffer->description !!}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Requirements Card -->
                        <div class="bg-white rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-100 p-10">
                            <h3 class="text-2xl font-black text-slate-900 mb-10 flex items-center gap-4">
                                <span class="w-2 h-8 bg-emerald-500 rounded-full"></span>
                                Profil Recherché
                            </h3>
                            
                            <div class="space-y-10">
                                <div>
                                    <p class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-6">Compétences attendues</p>
                                    <div class="flex flex-wrap gap-3">
                                        @foreach($jobOffer->skills as $skill)
                                            <div class="flex items-center gap-3 px-5 py-3 rounded-2xl text-sm font-bold transition-all duration-300 {{ $skill->pivot->is_required ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-slate-100 text-slate-600' }}">
                                                {{ $skill->label }}
                                                @if($skill->pivot->is_required)
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    @if($jobOffer->languages->count() > 0)
                                    <div class="p-8 bg-blue-50/50 rounded-3xl border border-blue-100/50">
                                        <p class="text-xs font-black text-blue-400 uppercase tracking-[0.2em] mb-6">Langues</p>
                                        <div class="space-y-3">
                                            @foreach($jobOffer->languages as $lang)
                                                <div class="flex items-center justify-between p-4 bg-white rounded-2xl shadow-sm border border-blue-100">
                                                    <span class="text-sm font-black text-blue-900">{{ $lang->label }}</span>
                                                    <span class="px-3 py-1 rounded-xl bg-blue-100 text-blue-700 text-[10px] font-black tracking-widest uppercase">{{ $lang->pivot->level }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif

                                    @if($jobOffer->permits->count() > 0)
                                    <div class="p-8 bg-amber-50/50 rounded-3xl border border-amber-100/50">
                                        <p class="text-xs font-black text-amber-500 uppercase tracking-[0.2em] mb-6">Mobilité</p>
                                        <div class="flex flex-wrap gap-3">
                                            @foreach($jobOffer->permits as $permit)
                                                <div class="px-5 py-3 bg-white text-amber-700 border border-amber-100 rounded-2xl text-sm font-extrabold flex items-center gap-3 shadow-sm">
                                                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-600">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1"></path></svg>
                                                    </div>
                                                    Permis {{ $permit->value }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Colonne Latérale -->
                    <div class="lg:col-span-4 space-y-8 lg:sticky lg:top-32">
                        
                        <!-- AI Match Card -->
                        <div class="bg-slate-900 rounded-[2.5rem] shadow-2xl shadow-slate-200 overflow-hidden text-white border border-slate-800">
                            <div class="p-10 text-center bg-gradient-to-br from-slate-800 to-slate-950">
                                <p class="text-[10px] font-black uppercase tracking-[0.3em] text-indigo-400 mb-10">Analyse de Matching</p>
                                
                                <div class="relative inline-flex items-center justify-center mb-6">
                                    <svg class="w-48 h-48 transform -rotate-90">
                                        <circle cx="96" cy="96" r="84" stroke="currentColor" stroke-width="12" fill="transparent" class="text-slate-800/50"></circle>
                                        <circle cx="96" cy="96" r="84" stroke="currentColor" stroke-width="12" fill="transparent" stroke-dasharray="527" stroke-dashoffset="{{ 527 - (527 * ($match->final_score ?? $match->pre_score)) / 100 }}" class="{{ $match->final_score >= 70 ? 'text-indigo-500' : ($match->final_score >= 40 ? 'text-amber-500' : 'text-rose-500') }} transition-all duration-1000 ease-out"></circle>
                                    </svg>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <span class="text-6xl font-black tracking-tighter">{{ $match->final_score ?? $match->pre_score }}<span class="text-2xl text-indigo-400/50">%</span></span>
                                        <span class="text-[10px] font-black text-slate-500 mt-2 uppercase tracking-[0.2em]">Match Score</span>
                                    </div>
                                </div>
                            </div>

                            <div class="p-10 pt-0 space-y-8">
                                @if($match->analyzed_at)
                                    <div class="space-y-6">
                                        <div class="p-6 bg-white/5 rounded-3xl border border-white/10">
                                            <h4 class="text-[10px] font-black uppercase tracking-widest text-indigo-400 mb-4 flex items-center gap-3">
                                                <div class="w-2 h-2 rounded-full bg-indigo-500 shadow-[0_0_10px_rgb(99,102,241)]"></div>
                                                Points Forts
                                            </h4>
                                            <ul class="space-y-3">
                                                @foreach($match->strengths ?? [] as $strength)
                                                    <li class="text-sm text-slate-300 flex items-start gap-3">
                                                        <span class="text-indigo-500 font-black mt-0.5">•</span> {{ $strength }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>

                                        <div class="p-6 bg-white/5 rounded-3xl border border-white/10">
                                            <h4 class="text-[10px] font-black uppercase tracking-widest text-rose-400 mb-4 flex items-center gap-3">
                                                <div class="w-2 h-2 rounded-full bg-rose-500 shadow-[0_0_10px_rgb(244,63,94)]"></div>
                                                Points d'attention
                                            </h4>
                                            <ul class="space-y-3">
                                                @foreach($match->weaknesses ?? [] as $weakness)
                                                    <li class="text-sm text-slate-300 flex items-start gap-3">
                                                        <span class="text-rose-500 font-black mt-0.5">•</span> {{ $weakness }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @else
                                    <div class="p-8 bg-white/5 rounded-3xl text-center border border-dashed border-white/10">
                                        <div class="w-12 h-12 bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                            <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                        </div>
                                        <p class="text-xs font-bold text-slate-400 mb-2 tracking-wide">Analyse IA disponible</p>
                                        <p class="text-[10px] text-slate-500 leading-relaxed mb-6">Obtenez une analyse détaillée de vos points forts et faibles par rapport à cette offre.</p>
                                        
                                        <form action="{{ route('jobs.match', $jobOffer) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="w-full py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl font-black text-[10px] uppercase tracking-widest transition-all">
                                                Lancer l'analyse IA
                                            </button>
                                        </form>
                                    </div>
                                @endif

                                <a href="https://www.leforem.be/recherche-offres/offre-detail/{{ $jobOffer->forem_id }}" target="_blank" class="group w-full py-5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-[1.5rem] font-black text-xs uppercase tracking-[0.2em] transition-all duration-300 shadow-2xl shadow-indigo-900/40 flex items-center justify-center gap-3">
                                    Voir sur Le Forem
                                    <svg class="w-4 h-4 transform group-hover:translate-x-1.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                                </a>

                                <form action="{{ route('jobs.refresh', $jobOffer) }}" method="POST" class="mt-4">
                                    @csrf
                                    <button type="submit" class="w-full py-3 bg-slate-100 hover:bg-slate-200 text-slate-500 rounded-xl font-bold text-[10px] uppercase tracking-widest transition-all flex items-center justify-center gap-2">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        Rafraîchir les données
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Recruiter Card -->
                        <div class="bg-white rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 p-10">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-8">Contact Recruteur</h4>
                            <div class="flex items-center gap-5 mb-8">
                                <div class="w-14 h-14 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300 border border-slate-100">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                </div>
                                <div>
                                    <p class="font-black text-slate-900 text-lg">{{ $jobOffer->contact_name }}</p>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-0.5">Responsable Recrutement</p>
                                </div>
                            </div>
                            
                            <div class="space-y-4">
                                @if($jobOffer->contact_email)
                                    <a href="mailto:{{ $jobOffer->contact_email }}" class="flex items-center gap-4 p-5 rounded-2xl bg-slate-50 text-slate-700 hover:bg-indigo-600 hover:text-white transition-all duration-300 group">
                                        <div class="w-8 h-8 rounded-lg bg-white shadow-sm flex items-center justify-center text-slate-400 group-hover:bg-indigo-500 group-hover:text-white transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                        </div>
                                        <span class="text-sm font-black truncate">{{ $jobOffer->contact_email }}</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
