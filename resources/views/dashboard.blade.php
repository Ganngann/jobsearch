<x-app-layout>
    <div 
        x-data="dashboardApp({
            initialSelectedId: {{ Js::from($jobOffers->first()?->forem_id) }},
            csrfToken: {{ Js::from(csrf_token()) }},
            filters: {
                sort: {{ Js::from(request('sort', 'score_desc')) }},
                min_score: {{ Js::from(request('min_score', 0)) }},
                metier_id: {{ Js::from(request('metier_id')) }},
                employer_id: {{ Js::from(request('employer_id')) }},
                rome: {{ Js::from(request('rome')) }},
                q: {{ Js::from(request('q')) }}
            }
        })" 
        class="h-[calc(100vh-112px)] flex overflow-hidden bg-slate-50"
    >
        
        <!-- SIDEBAR: Filtres & Exploration -->
        <aside class="w-80 border-r border-slate-200 bg-white flex flex-col shrink-0 overflow-y-auto custom-scrollbar">
            <div class="p-6 space-y-10">

                <!-- Section: Maturité (Gamification) -->
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

                <!-- Section: Top Métiers -->
                <div x-data="{ metierSearch: '' }">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Explorer par Métier</h3>
                    </div>
                    
                    <div class="mb-4">
                        <input 
                            type="text" 
                            x-model="metierSearch" 
                            placeholder="Filtrer un métier..." 
                            class="w-full bg-slate-50 border-0 rounded-xl px-4 py-2 text-[11px] font-bold text-slate-600 focus:ring-1 focus:ring-indigo-500 transition-all"
                        >
                    </div>

                    <div class="space-y-1 mb-8">
                        <button 
                            @click="setMetier(null)"
                            :class="(!filters.metier_id && !filters.rome) ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
                            class="w-full text-left px-4 py-3 rounded-xl text-sm font-bold transition-all"
                        >
                            Tous les métiers
                        </button>

                        <!-- Active ROME Filter Badge -->
                        <template x-if="filters.rome">
                            <div class="px-4 py-3 bg-violet-100 text-violet-700 rounded-xl text-xs font-black flex items-center justify-between group">
                                <span>DÉCOUVERTE : <span x-text="filters.rome"></span></span>
                                <button @click="setMetier(null)" class="opacity-50 hover:opacity-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>

                    <div class="space-y-1 max-h-[400px] overflow-y-auto custom-scrollbar pr-1">
                        @php $preferredIds = $user->preferredMetiers->pluck('id')->toArray(); @endphp
                        @foreach($topMetiers as $metier)
                            @php 
                                $isDiscovery = !in_array($metier->id, $preferredIds);
                            @endphp
                            <button 
                                x-show="metierSearch === '' || '{{ strtolower(addslashes($metier->label)) }}'.includes(metierSearch.toLowerCase())"
                                @click="setMetier({{ $metier->id }})"
                                :class="filters.metier_id == {{ $metier->id }} ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'text-slate-600 hover:bg-slate-50'"
                                class="w-full text-left px-4 py-3 rounded-xl transition-all group relative"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-xs font-bold truncate">{{ $metier->label }}</span>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[9px] opacity-40 font-black uppercase tracking-tighter">{{ $metier->job_offers_count }} offres</span>
                                            @if($isDiscovery)
                                                <span class="text-[9px] text-amber-500 font-black uppercase tracking-tighter flex items-center gap-1">
                                                    ✨ Explorer
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Section: Top Employeurs -->
                <div x-data="{ employerSearch: '' }">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Top Employeurs</h3>
                    
                    <div class="mb-4">
                        <input 
                            type="text" 
                            x-model="employerSearch" 
                            placeholder="Filtrer un employeur..." 
                            class="w-full bg-slate-50 border-0 rounded-xl px-4 py-2 text-[11px] font-bold text-slate-600 focus:ring-1 focus:ring-indigo-500 transition-all"
                        >
                    </div>

                    <div class="space-y-1 max-h-[400px] overflow-y-auto custom-scrollbar pr-1">
                        <button 
                            @click="setEmployer(null)"
                            x-show="employerSearch === ''"
                            :class="!filters.employer_id ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'text-slate-600 hover:bg-slate-50'"
                            class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-between"
                        >
                            <span>Tous les employeurs</span>
                        </button>
                        @foreach($topEmployers as $employer)
                            <button 
                                x-show="employerSearch === '' || '{{ strtolower(addslashes($employer->label)) }}'.includes(employerSearch.toLowerCase())"
                                @click="setEmployer({{ $employer->id }})"
                                :class="filters.employer_id == {{ $employer->id }} ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'text-slate-600 hover:bg-slate-50'"
                                class="w-full text-left px-4 py-3 rounded-xl transition-all group relative"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-xs font-bold truncate">{{ $employer->label }}</span>
                                        <span class="text-[9px] opacity-40 font-black uppercase tracking-tighter mt-0.5">{{ $employer->job_offers_count }} offres</span>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

            </div>
        </aside>

        <!-- MIDDLE: Liste des offres -->
        <main class="flex-1 flex flex-col border-r border-slate-200 min-w-[450px]">
            <!-- Header de liste -->
            <div class="p-6 bg-white border-b border-slate-100">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-xl font-black text-slate-900">Offres Emploi</h1>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Exploration en temps réel</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="relative group">
                            <input 
                                type="text" 
                                x-model="filters.q" 
                                @input.debounce.500ms="refreshList()"
                                placeholder="Rechercher..." 
                                class="w-48 bg-slate-100 border-0 rounded-xl px-4 py-2 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 transition-all pl-10"
                            >
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        
                        <button 
                            @click="triggerTopAi()" 
                            class="p-2 bg-amber-50 text-amber-600 rounded-xl hover:bg-amber-100 transition-all shadow-sm flex items-center gap-2 border border-amber-100 relative group"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            <span class="text-[10px] font-black uppercase tracking-widest">Analyse IA (Top 20)</span>
                            
                            <!-- Tooltip technique sans langue de bois -->
                            <div class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-64 p-3 bg-slate-900 text-white text-[10px] font-bold rounded-xl opacity-0 group-hover:opacity-100 transition-all pointer-events-none z-[100] text-left shadow-2xl border border-white/10">
                                <div class="absolute bottom-full left-1/2 -translate-x-1/2 border-8 border-transparent border-b-slate-900"></div>
                                <p class="text-indigo-400 uppercase tracking-widest text-[8px] mb-2 font-black">Audit Sémantique Profond</p>
                                <ul class="space-y-2 list-none p-0">
                                    <li class="flex gap-2">
                                        <span class="text-indigo-500">▹</span>
                                        <span>Confronte vos <strong>récits d'expérience</strong> aux exigences réelles du poste.</span>
                                    </li>
                                    <li class="flex gap-2">
                                        <span class="text-indigo-500">▹</span>
                                        <span>Détecte les <strong>soft-skills invisibles</strong> (résilience, adaptabilité) dans votre ton et vos faits.</span>
                                    </li>
                                    <li class="flex gap-2">
                                        <span class="text-indigo-500">▹</span>
                                        <span>Valide l'<strong>adéquation culturelle</strong> et la faisabilité contextuelle (hors simples mots-clés).</span>
                                    </li>
                                </ul>
                            </div>
                        </button>
                    </div>

                </div>

                <!-- Onglets (Level 5 doc) -->
                <div class="flex p-1 bg-slate-100 rounded-2xl">
                    <button 
                        @click="filters.sort = 'score_desc'; refreshList()"
                        :class="filters.sort === 'score_desc' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        class="flex-1 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex flex-col items-center justify-center gap-0.5"
                    >
                        <span>Score Global</span>
                        <span class="text-[8px] opacity-60 font-bold">Critères & Conditions</span>
                    </button>
                    <button 
                        @click="filters.sort = 'vector_desc'; refreshList()"
                        :class="filters.sort === 'vector_desc' ? 'bg-white text-violet-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        class="flex-1 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex flex-col items-center justify-center gap-0.5"
                    >
                        <span>Potentiel IA</span>
                        <span class="text-[8px] opacity-60 font-bold">Similarité Sémantique</span>
                    </button>
                </div>
            </div>


            <!-- Miroir des Possibles Button -->
            <div class="px-4 py-4">
                <a href="{{ route('discovery.index') }}" class="w-full flex items-center justify-center gap-2 py-3 bg-gradient-to-r from-indigo-600 to-violet-600 text-white rounded-xl font-bold shadow-xl shadow-indigo-100 hover:scale-[1.02] transition-all group">
                    <svg class="w-5 h-5 group-hover:animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Miroir des Possibles
                </a>
            </div>

            <!-- Scrollable List -->
            <div id="offers-scroll-container" class="flex-1 overflow-y-auto custom-scrollbar p-4 space-y-3 bg-slate-50/50">
                <div id="offers-container" class="space-y-3">
                    @include('job-offers.partials.list-items', ['jobOffers' => $jobOffers])
                </div>

                <!-- Sentinel for Infinite Scroll -->
                <div 
                    x-intersect.margin.200px="loadMore()" 
                    x-show="!noMoreData" 
                    class="py-10 flex flex-col items-center justify-center space-y-4"
                >
                    <template x-if="loadingMore">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-4">Chargement de la suite...</p>
                        </div>
                    </template>
                </div>

                <div x-show="noMoreData" class="py-10 text-center">
                    <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest italic">Toutes les offres ont été chargées</p>
                </div>
            </div>
        </main>

        <!-- RIGHT: Panneau de prévisualisation -->
        <section class="flex-1 bg-white relative hidden lg:flex flex-col">
            <div x-show="!selectedId" class="h-full flex flex-col items-center justify-center p-12 text-center bg-slate-50/50">

                <div class="w-24 h-24 bg-white rounded-3xl shadow-xl shadow-slate-200 flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                </div>
                <h3 class="text-lg font-black text-slate-900">Aucune offre sélectionnée</h3>
                <p class="mt-2 text-sm text-slate-400 font-medium max-w-xs">Cliquez sur une offre dans la liste de gauche pour voir l'analyse détaillée.</p>
            </div>

            <div x-show="selectedId" class="h-full relative">
                <!-- Loader -->
                <div x-show="previewLoading" class="absolute inset-0 z-50 bg-white/80 backdrop-blur-sm flex items-center justify-center">
                    <div class="flex flex-col items-center">
                        <div class="w-12 h-12 border-4 border-indigo-100 border-t-indigo-600 rounded-full animate-spin"></div>
                        <p class="mt-4 text-xs font-black text-indigo-600 uppercase tracking-widest">Analyse en cours...</p>
                    </div>
                </div>

                <!-- Preview Content Area -->
                <div x-html="previewHtml" class="h-full">
                    <!-- Le contenu sera injecté ici via AJAX -->
                </div>
            </div>
        </section>

        <!-- TOAST CONTAINER (JIT Feedback) -->
        <div 
            id="toast-container" 
            class="fixed bottom-8 right-8 z-[200] flex flex-col gap-3 pointer-events-none"
        ></div>

    </div>

    <script>
        // dashboardApp logic moved to resources/js/dashboard.js
    </script>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

        @keyframes score-bounce {
            0% { transform: scale(1); }
            50% { transform: scale(1.15); filter: brightness(1.2); }
            100% { transform: scale(1); }
        }
        .animate-score-change {
            animation: score-bounce 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes toast-in {
            0% { transform: translateX(100%) scale(0.9); opacity: 0; }
            100% { transform: translateX(0) scale(1); opacity: 1; }
        }
        .animate-toast-in {
            animation: toast-in 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
    </style>
</x-app-layout>

