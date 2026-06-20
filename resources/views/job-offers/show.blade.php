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

        <div class="py-12" x-data="jobDetails({ csrfToken: {{ Js::from(csrf_token()) }} })">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
                    
                    <!-- Colonne Principale (Structure type Forem) -->
                    <div class="lg:col-span-8 space-y-6">

                        <!-- En-tête de l'offre -->
                        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-10 mb-6">
                            <div class="flex flex-col md:flex-row md:items-start justify-between gap-8">
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
                                    </div>
                                </div>

                                <div class="shrink-0 pt-2">
                                    @if($jobOffer->employer->logo_base64 && strlen($jobOffer->employer->logo_base64) > 100)
                                        <div class="w-32 h-32 p-6 bg-slate-50 rounded-[2rem] border border-slate-100 shadow-inner flex items-center justify-center">
                                            <img src="{{ route('employers.logo', $jobOffer->employer_id) }}" class="w-full h-full object-contain" alt="Logo">
                                        </div>
                                    @else
                                        <div class="w-32 h-32 rounded-[2rem] bg-gradient-to-br from-indigo-500 to-violet-600 flex flex-col items-center justify-center text-white shadow-2xl shadow-indigo-200">
                                            <span class="text-4xl font-black">{{ substr($jobOffer->employer->label, 0, 1) }}</span>
                                            <span class="text-[10px] font-black uppercase tracking-widest mt-1 opacity-60">Employeur</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Indice de Confort (Matching Soustractif) -->
                        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden mb-6">
                            <div class="px-8 py-5 bg-emerald-50/50 border-b border-emerald-100 flex items-center justify-between">
                                <h3 class="text-lg font-bold text-emerald-900">Indice de Confort</h3>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600/50">Score d'attractivité :</span>
                                    <span class="px-3 py-1 bg-emerald-600 text-white rounded-full text-xs font-black">{{ $match->pre_score }}%</span>
                                </div>
                            </div>
                            <div class="p-8">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                                    {{-- Colonne des Frictions --}}
                                    <div>
                                        <h4 class="text-[10px] font-black uppercase tracking-widest text-rose-400 mb-6 flex items-center gap-2">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M20 12H4"></path></svg>
                                            Frictions & Handicaps
                                        </h4>
                                        <div class="space-y-4">
                                            @forelse($match->pre_score_details['penalties'] ?? [] as $penalty)
                                                <div class="flex flex-col gap-1.5 p-4 bg-rose-50/30 rounded-2xl border border-rose-100/50">
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs font-bold text-slate-700">{{ $penalty['label'] }}</span>
                                                        <span class="text-xs font-black text-rose-600">{{ $penalty['value'] }}</span>
                                                    </div>
                                                    @if(!empty($penalty['items']))
                                                        <div class="flex flex-wrap gap-1">
                                                            @foreach($penalty['items'] as $item)
                                                                <span class="text-[9px] bg-white/80 text-rose-500 px-2 py-0.5 rounded-lg border border-rose-100 font-bold uppercase">{{ $item }}</span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @empty
                                                <div class="flex items-center gap-3 p-4 bg-emerald-50/30 rounded-2xl border border-emerald-100/50">
                                                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                    <span class="text-xs font-bold text-emerald-700">Aucune friction détectée</span>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>

                                    {{-- Colonne des Bonus --}}
                                    <div>
                                        <h4 class="text-[10px] font-black uppercase tracking-widest text-emerald-500 mb-6 flex items-center gap-2">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                                            Bonus & Affinités
                                        </h4>
                                        <div class="space-y-4">
                                            <div class="flex justify-between items-center p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                                <span class="text-xs font-bold text-slate-500">Base de départ</span>
                                                <span class="text-xs font-black text-slate-400">+{{ $match->pre_score_details['base'] ?? 100 }}</span>
                                            </div>
                                            @foreach($match->pre_score_details['bonuses'] ?? [] as $bonus)
                                                <div class="flex flex-col gap-1.5 p-4 bg-emerald-50/30 rounded-2xl border border-emerald-100/50">
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs font-bold text-slate-700">{{ $bonus['label'] }}</span>
                                                        <span class="text-xs font-black text-emerald-600">+{{ $bonus['value'] }}</span>
                                                    </div>
                                                    @if(!empty($bonus['items']))
                                                        <div class="flex flex-wrap gap-1">
                                                            @foreach($bonus['items'] as $item)
                                                                <span class="text-[9px] bg-white/80 text-emerald-500 px-2 py-0.5 rounded-lg border border-emerald-100 font-bold uppercase">{{ $item }}</span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- 1. Section: La Dimension Humaine (Analyse IA) -->
                        @if($match->analyzed_at)
                        <div class="bg-slate-900 rounded-3xl shadow-2xl border border-slate-800 overflow-hidden mb-6 text-white">
                            <div class="px-8 py-5 bg-gradient-to-r from-slate-800 to-indigo-900 border-b border-slate-700 flex items-center justify-between">
                                <h3 class="text-lg font-bold text-indigo-100 flex items-center gap-3">
                                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    La Dimension Humaine
                                </h3>
                                <div class="flex items-center gap-3">
                                    @php 
                                        $matchModel = config('services.gemini.models.match');
                                        $remainingMatch = Auth::user()->getAiRemainingPoints($matchModel);
                                        $profilePublished = Auth::user()->profile_published_at;
                                        $preScoreDelta = abs(($match->pre_score ?? 0) - ($match->ai_at_pre_score ?? 0));
                                        $showRelancer = !$match->analyzed_at 
                                            || ($profilePublished && $match->analyzed_at->lt($profilePublished)) 
                                            || $preScoreDelta > 2;
                                    @endphp
                                    @if($showRelancer)
                                    <form action="{{ route('jobs.match', $jobOffer) }}" method="POST">
                                        @csrf
                                        <button type="submit" 
                                                class="group flex items-center gap-2 px-4 py-2 {{ $remainingMatch > 0 ? 'bg-white/5 hover:bg-white/10 border-white/10' : 'bg-rose-500/10 border-rose-500/20 cursor-not-allowed opacity-50' }} border rounded-xl transition-all duration-300"
                                                {{ $remainingMatch > 0 ? '' : 'disabled' }}>
                                            <svg class="w-3.5 h-3.5 {{ $remainingMatch > 0 ? 'text-indigo-400 group-hover:rotate-180' : 'text-rose-400' }} transition-transform duration-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                            <span class="text-[10px] font-bold {{ $remainingMatch > 0 ? 'text-slate-300' : 'text-rose-300' }} uppercase tracking-widest">
                                                {{ $remainingMatch > 0 ? "Relancer l'analyse ($remainingMatch)" : "Quota épuisé" }}
                                            </span>
                                        </button>
                                    </form>
                                    @endif
                                    <span class="px-3 py-1 bg-indigo-500/20 text-indigo-300 rounded-full text-[10px] font-black uppercase tracking-widest border border-indigo-500/30">Analyse IA Narrative</span>
                                </div>
                            </div>
                            <div class="p-8">
                                <div class="prose prose-invert max-w-none">
                                    <div class="bg-white/5 p-6 rounded-2xl border border-white/10 mb-6">
                                        <h4 class="text-xs font-black uppercase tracking-[0.2em] text-indigo-400 mb-3">Analyse de vos récits</h4>
                                        <p class="text-sm text-slate-300 leading-relaxed italic">
                                            {{ $match->ai_analysis_narrative }}
                                        </p>
                                    </div>
                                    
                                    <div class="flex items-start gap-4 p-6 bg-indigo-600/10 rounded-2xl border border-indigo-500/20">
                                        <div class="w-10 h-10 shrink-0 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-900/50">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-black uppercase tracking-[0.2em] text-indigo-300 mb-1">Conseil d'expert</h4>
                                            <p class="text-sm font-medium text-indigo-100">
                                                {{ $match->ai_recommendation }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- 2. Section: Poste à pourvoir -->
                        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden" x-data="{ open: true }">
                            <button @click="open = !open" class="w-full px-8 py-5 flex items-center justify-between bg-slate-50/50 border-b border-slate-100 group transition-all">
                                <h3 class="text-lg font-bold text-slate-800">Poste à pourvoir</h3>
                                <svg class="w-5 h-5 text-slate-400 group-hover:text-indigo-500 transition-transform duration-300" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <div class="p-8 space-y-8" x-show="open" x-collapse>
                                @if(isset($jobOffer->raw_data['descriptionJob']) && $jobOffer->raw_data['descriptionJob'])
                                <div>
                                    <h4 class="text-md font-bold text-slate-900 mb-4">Responsabilité et missions</h4>
                                    <div class="prose prose-slate max-w-none text-slate-600 text-sm leading-relaxed">
                                        @purify($jobOffer->raw_data['descriptionJob'])
                                    </div>
                                </div>
                                @else
                                <div>
                                    <h4 class="text-md font-bold text-slate-900 mb-4">Description de l'offre</h4>
                                    <div class="prose prose-slate max-w-none text-slate-600 text-sm leading-relaxed">
                                        @purify($jobOffer->description)
                                    </div>
                                </div>
                                @endif

                                <div>
                                    <h4 class="text-md font-bold text-slate-900 mb-4">Lieu(x) de travail</h4>
                                    <ul class="space-y-2">
                                        @foreach($jobOffer->locations_json ?? [] as $loc)
                                            <li class="flex items-center gap-3 text-sm text-slate-600">
                                                <div class="w-1.5 h-1.5 rounded-full bg-slate-400"></div>
                                                {{ $loc }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Section: Votre profil -->
                        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden" x-data="{ open: true }">
                            <button @click="open = !open" class="w-full px-8 py-5 flex items-center justify-between bg-slate-50/50 border-b border-slate-100 group transition-all">
                                <h3 class="text-lg font-bold text-slate-800">Votre profil</h3>
                                <svg class="w-5 h-5 text-slate-400 group-hover:text-indigo-500 transition-transform duration-300" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <div class="p-8 space-y-10" x-show="open" x-collapse>
                                <div>
                                    <h4 class="text-md font-bold text-slate-900 mb-2">Métier</h4>
                                    <div class="flex items-center gap-3">
                                        <p class="text-sm text-slate-600">{{ $jobOffer->metier->label ?? 'Non spécifié' }}</p>
                                        @if($jobOffer->metier)
                                            <div class="flex items-center gap-1">
                                                @if(Auth::user()->preferredMetiers->contains($jobOffer->metier->id))
                                                    <span class="text-rose-500 p-1.5" title="Dans vos métiers favoris">
                                                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                                    </span>
                                                @elseif($isParentFavorite)
                                                    <div class="flex items-center gap-1 bg-rose-500 text-white px-2 py-0.5 rounded-full mr-1 shadow-sm" title="Famille ROME en favoris">
                                                        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                                        <span class="text-[8px] font-black uppercase">Famille</span>
                                                    </div>
                                                @else
                                                    <button 
                                                        @click="addMetier({{ $jobOffer->metier->id }})"
                                                        class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-xl transition-all"
                                                        title="Ajouter aux métiers favoris"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                                    </button>
                                                @endif

                                                <button 
                                                    @click="refuseMetier({{ $jobOffer->metier->id }})"
                                                    class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-xl transition-all flex items-center gap-2 group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                                    title="Écarter ce métier"
                                                    aria-label="Écarter ce métier"
                                                >
                                                    <svg class="w-4 h-4" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                    <span class="text-[10px] font-black uppercase tracking-widest opacity-0 group-hover:opacity-100 group-focus:opacity-100 transition-opacity">Écarter ce métier</span>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    <h4 class="text-md font-bold text-slate-900 mb-6">Expériences, langues et qualifications</h4>
                                    
                                    <!-- Table Expérience -->
                                    <div class="overflow-hidden border border-slate-200 rounded-xl mb-6">
                                        <table class="w-full text-left text-xs">
                                            <thead class="bg-slate-50 border-b border-slate-200">
                                                <tr>
                                                    <th class="px-4 py-3 font-bold text-slate-700">Expérience</th>
                                                    <th class="px-4 py-3 font-bold text-slate-700">Niveau d'expérience</th>
                                                    <th class="px-4 py-3 font-bold text-slate-700 text-center">Exigé</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @forelse($jobOffer->requiredExperiences as $exp)
                                                <tr>
                                                    <td class="px-4 py-3 text-slate-600">{{ $exp->label }}</td>
                                                    <td class="px-4 py-3 text-slate-600">{{ $exp->pivot->experience_label }}</td>
                                                    <td class="px-4 py-3 text-center text-slate-600">{{ $exp->pivot->is_required ? 'Oui' : 'Non' }}</td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="3" class="px-4 py-3 text-slate-400 text-center italic">Aucune expérience spécifique requise</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Table Compétences Techniques -->
                                    @php
                                        $techSkills = $jobOffer->skills->where('type', 'hard');
                                    @endphp
                                    @if($techSkills->count() > 0)
                                    <div class="overflow-hidden border border-slate-200 rounded-xl mb-6">
                                        <table class="w-full text-left text-xs">
                                            <thead class="bg-slate-50 border-b border-slate-200">
                                                <tr>
                                                    <th class="px-4 py-3 font-bold text-slate-700">Compétences professionnelles</th>
                                                    <th class="px-4 py-3 font-bold text-slate-700 text-center">Exigé</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach($techSkills as $skill)
                                                <tr>
                                                    <td class="px-4 py-3 text-slate-600">{{ $skill->label }}</td>
                                                    <td class="px-4 py-3 text-center text-slate-600">{{ $skill->pivot->is_required ? 'Oui' : 'Non' }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @endif

                                    <!-- Table Études -->
                                    @if($jobOffer->studies->count() > 0)
                                    <div class="overflow-hidden border border-slate-200 rounded-xl mb-6">
                                        <table class="w-full text-left text-xs">
                                            <thead class="bg-slate-50 border-b border-slate-200">
                                                <tr>
                                                    <th class="px-4 py-3 font-bold text-slate-700">Niveau d'études / Diplômes</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach($jobOffer->studies as $study)
                                                <tr>
                                                    <td class="px-4 py-3 text-slate-600">{{ $study->label }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @endif

                                    <!-- Table Langues -->
                                    @if($jobOffer->languages->count() > 0)
                                    <div class="overflow-hidden border border-slate-200 rounded-xl mb-6">
                                        <table class="w-full text-left text-xs">
                                            <thead class="bg-slate-50 border-b border-slate-200">
                                                <tr>
                                                    <th class="px-4 py-3 font-bold text-slate-700">Langue</th>
                                                    <th class="px-4 py-3 font-bold text-slate-700">Niveau</th>
                                                    <th class="px-4 py-3 font-bold text-slate-700 text-center">Exigé</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach($jobOffer->languages as $lang)
                                                <tr>
                                                    <td class="px-4 py-3 text-slate-600">{{ $lang->label }}</td>
                                                    <td class="px-4 py-3 text-slate-600">{{ $lang->pivot->level }}</td>
                                                    <td class="px-4 py-3 text-center text-slate-600">{{ $lang->pivot->is_required ? 'Oui' : 'Non' }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @endif

                                    <!-- Table Permis -->
                                    @if($jobOffer->permits->count() > 0)
                                    <div class="overflow-hidden border border-slate-200 rounded-xl">
                                        <table class="w-full text-left text-xs">
                                            <thead class="bg-slate-50 border-b border-slate-200">
                                                <tr>
                                                    <th class="px-4 py-3 font-bold text-slate-700">Permis de conduire</th>
                                                    <th class="px-4 py-3 font-bold text-slate-700 text-center">Exigé</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach($jobOffer->permits as $permit)
                                                <tr>
                                                    <td class="px-4 py-3 text-slate-600">Permis {{ $permit->value }} ({{ $permit->label }})</td>
                                                    <td class="px-4 py-3 text-center text-slate-600">Oui</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @endif

                                    <!-- Soft Skills Badges -->
                                    @php
                                        $softSkills = $jobOffer->skills->where('type', 'soft');
                                    @endphp
                                    @if($softSkills->count() > 0)
                                    <div class="mt-10 pt-8 border-t border-slate-100">
                                        <h4 class="text-md font-bold text-slate-900 mb-4">Compétences comportementales</h4>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($softSkills as $skill)
                                                <span class="px-4 py-2 bg-indigo-50 text-indigo-700 rounded-full text-sm font-medium border border-indigo-100 shadow-sm">
                                                    {{ $skill->label }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                @if(isset($jobOffer->raw_data['descriptionComment']) && $jobOffer->raw_data['descriptionComment'])
                                <div>
                                    <h4 class="text-md font-bold text-slate-900 mb-4">Description complémentaire</h4>
                                    <div class="prose prose-slate max-w-none text-slate-600 text-sm leading-relaxed">
                                        @purify($jobOffer->raw_data['descriptionComment'])
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- 3. Section: Commentaire général -->
                        @if(isset($jobOffer->raw_data['commentaireGeneral']) && $jobOffer->raw_data['commentaireGeneral'])
                        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden" x-data="{ open: true }">
                            <button @click="open = !open" class="w-full px-8 py-5 flex items-center justify-between bg-slate-50/50 border-b border-slate-100 group transition-all">
                                <h3 class="text-lg font-bold text-slate-800">Commentaire général</h3>
                                <svg class="w-5 h-5 text-slate-400 group-hover:text-indigo-500 transition-transform duration-300" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <div class="p-8" x-show="open" x-collapse>
                                <div class="prose prose-slate max-w-none text-slate-600 text-sm leading-relaxed">
                                    @purify($jobOffer->raw_data['commentaireGeneral'])
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- 4. Section: Condition du poste -->
                        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden" x-data="{ open: true }">
                            <button @click="open = !open" class="w-full px-8 py-5 flex items-center justify-between bg-slate-50/50 border-b border-slate-100 group transition-all">
                                <h3 class="text-lg font-bold text-slate-800">Condition du poste</h3>
                                <svg class="w-5 h-5 text-slate-400 group-hover:text-indigo-500 transition-transform duration-300" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <div class="p-8 space-y-10" x-show="open" x-collapse>
                                <div class="overflow-hidden border border-slate-200 rounded-xl">
                                    <table class="w-full text-left text-xs">
                                        <thead class="bg-slate-50 border-b border-slate-200">
                                            <tr>
                                                <th class="px-4 py-3 font-bold text-slate-700">Type de Contrat</th>
                                                <th class="px-4 py-3 font-bold text-slate-700 text-center">Date de début</th>
                                                <th class="px-4 py-3 font-bold text-slate-700 text-center">Date de fin</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="px-4 py-3 text-slate-600">{{ $jobOffer->contract_type }}</td>
                                                <td class="px-4 py-3 text-center text-slate-600">{{ $jobOffer->start_date ? $jobOffer->start_date->format('d/m/Y') : '-' }}</td>
                                                <td class="px-4 py-3 text-center text-slate-600">{{ $jobOffer->expires_at ? $jobOffer->expires_at->format('d/m/Y') : '-' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="flex flex-col md:flex-row gap-8 py-6 border-y border-slate-100">
                                    <div class="flex-1">
                                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Régime de travail</h4>
                                        <p class="text-sm font-bold text-slate-700">{{ $jobOffer->working_regime }}</p>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Heures / semaine</h4>
                                        <p class="text-sm font-bold text-slate-700">{{ $jobOffer->working_hours ?? 'Non précisé' }}h</p>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Précision</h4>
                                        <p class="text-sm font-bold text-slate-700">{{ $jobOffer->working_regime_detail ?? ($jobOffer->working_regime == 'Temps plein' ? 'Standard' : 'N/A') }}</p>
                                    </div>
                                </div>

                                @if($jobOffer->benefits_comments || (isset($jobOffer->raw_data['benefitsComments']) && $jobOffer->raw_data['benefitsComments']))
                                <div>
                                    <h4 class="text-md font-bold text-slate-900 mb-4">Ce que nous offrons :</h4>
                                    <div class="prose prose-slate max-w-none text-slate-600 text-sm leading-relaxed">
                                        @purify($jobOffer->raw_data['benefitsComments'] ?? $jobOffer->benefits_comments)
                                    </div>
                                </div>
                                @endif
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
                                        @if($match->exists)
                                            <circle cx="96" cy="96" r="84" stroke="currentColor" stroke-width="12" fill="transparent" stroke-dasharray="527" stroke-dashoffset="{{ 527 - (527 * ($match->final_score ?? $match->pre_score)) / 100 }}" class="{{ ($match->final_score ?? $match->pre_score) >= 70 ? 'text-indigo-500' : (($match->final_score ?? $match->pre_score) >= 40 ? 'text-amber-500' : 'text-rose-500') }} transition-all duration-1000 ease-out"></circle>
                                        @endif
                                    </svg>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        @if($match->exists)
                                            <span class="text-6xl font-black tracking-tighter">{{ $match->final_score ?? $match->pre_score }}<span class="text-2xl text-indigo-400/50">%</span></span>
                                        @else
                                            <span class="text-2xl font-black text-slate-500 uppercase tracking-widest">Calcul...</span>
                                        @endif
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
                                            @php 
                                                $matchModel = config('services.gemini.models.match');
                                                $remainingMatch = Auth::user()->getAiRemainingPoints($matchModel);
                                            @endphp
                                            <button type="submit" 
                                                    class="w-full py-3 {{ $remainingMatch > 0 ? 'bg-white/10 hover:bg-white/20' : 'bg-rose-500/10 text-rose-300 cursor-not-allowed' }} text-white rounded-xl font-black text-[10px] uppercase tracking-widest transition-all flex items-center justify-center gap-3"
                                                    {{ $remainingMatch > 0 ? '' : 'disabled' }}>
                                                @if($remainingMatch > 0)
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                                    Lancer l'analyse IA
                                                    <span class="ml-auto px-2 py-0.5 bg-indigo-500/20 text-indigo-300 rounded-md">{{ $remainingMatch }} restants</span>
                                                @else
                                                    Quota épuisé pour aujourd'hui
                                                @endif
                                            </button>
                                        </form>
                                    </div>
                                @endif

                                <a href="https://www.leforem.be/recherche-offres/offre-detail/{{ $jobOffer->forem_id }}" target="_blank" class="group w-full py-5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-[1.5rem] font-black text-xs uppercase tracking-[0.2em] transition-all duration-300 shadow-2xl shadow-indigo-900/40 flex items-center justify-center gap-3">
                                    Voir sur Le Forem
                                    <svg class="w-4 h-4 transform group-hover:translate-x-1.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                                </a>

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

                                @if($jobOffer->apply_url)
                                    <a href="{{ $jobOffer->apply_url }}" target="_blank" class="flex items-center gap-4 p-5 rounded-2xl bg-indigo-600 text-white hover:bg-indigo-700 transition-all duration-300 group shadow-lg shadow-indigo-100">
                                        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center text-white">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                        </div>
                                        <span class="text-sm font-black">Postuler en ligne</span>
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
