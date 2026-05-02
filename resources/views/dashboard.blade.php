<x-app-layout>
    <div x-data="dashboardApp()" class="h-[calc(100vh-64px)] flex overflow-hidden bg-slate-50">
        
        <!-- SIDEBAR: Filtres & Exploration -->
        <aside class="w-80 border-r border-slate-200 bg-white flex flex-col shrink-0 overflow-y-auto custom-scrollbar">
            <div class="p-6 space-y-10">
                
                <!-- Section: Tri & Vue -->
                <div>
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Affichage & Tri</h3>
                    <div class="space-y-2">
                        <select 
                            x-model="filters.sort" 
                            @change="refreshList()"
                            class="w-full bg-slate-50 border-0 rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 transition-all"
                        >
                            <option value="score_desc">🏆 Meilleur Match</option>
                            <option value="recent">📅 Plus récents</option>
                        </select>
                    </div>
                </div>

                <!-- Section: Score Minimum -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Score Minimum</h3>
                        <span class="text-xs font-black text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-lg" x-text="filters.min_score + '%'"></span>
                    </div>
                    <input 
                        type="range" 
                        x-model="filters.min_score" 
                        @change="refreshList()"
                        min="0" max="90" step="10"
                        class="w-full h-1.5 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-indigo-600"
                    >
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

                    <div class="space-y-1 max-h-[300px] overflow-y-auto custom-scrollbar pr-1">
                        <button 
                            @click="setMetier(null)"
                            x-show="metierSearch === ''"
                            :class="!filters.metier_id ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'text-slate-600 hover:bg-slate-50'"
                            class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-between"
                        >
                            <span>Tous les métiers</span>
                        </button>
                        @foreach($topMetiers as $metier)
                            <button 
                                x-show="metierSearch === '' || '{{ strtolower(addslashes($metier->label)) }}'.includes(metierSearch.toLowerCase())"
                                @click="setMetier({{ $metier->id }})"
                                :class="filters.metier_id == {{ $metier->id }} ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'text-slate-600 hover:bg-slate-50'"
                                class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-between group"
                            >
                                <span class="truncate pr-2">{{ $metier->label }}</span>
                                <span class="text-[9px] opacity-60">{{ $metier->job_offers_count }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Section: Top Employeurs -->
                <div>
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Top Employeurs</h3>
                    <div class="space-y-1">
                        @foreach($topEmployers as $employer)
                            <button 
                                @click="setEmployer({{ $employer->id }})"
                                :class="filters.employer_id == {{ $employer->id }} ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'text-slate-600 hover:bg-slate-50'"
                                class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-between"
                            >
                                <span class="truncate pr-2">{{ $employer->label }}</span>
                                <span class="text-[9px] opacity-60">{{ $employer->job_offers_count }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

            </div>
        </aside>

        <!-- MIDDLE: Liste des offres -->
        <main class="flex-1 flex flex-col border-r border-slate-200 min-w-[450px]">
            <!-- Header de liste -->
            <div class="p-6 bg-white border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-black text-slate-900">Offres Emploi</h1>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Exploration en temps réel</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-black text-slate-400 px-3 py-1 bg-slate-100 rounded-full">
                        {{ $jobOffers->total() }} résultats
                    </span>
                </div>
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

    </div>

    <script>
        function dashboardApp() {
            return {
                selectedId: '{{ $jobOffers->first()?->forem_id }}',
                previewLoading: false,
                previewHtml: '',
                filters: {
                    sort: '{{ request('sort', 'score_desc') }}',
                    min_score: '{{ request('min_score', 0) }}',
                    metier_id: '{{ request('metier_id') }}',
                    employer_id: '{{ request('employer_id') }}'
                },
                page: 1,
                loadingMore: false,
                noMoreData: false,

                init() {
                    if (this.selectedId) {
                        this.selectOffer(this.selectedId);
                    }
                },

                selectOffer(id) {
                    this.selectedId = id;
                    this.previewLoading = true;
                    fetch(`/jobs/${id}/preview`)
                        .then(res => res.text())
                        .then(html => {
                            this.previewHtml = html;
                            this.previewLoading = false;
                        });
                },

                setMetier(id) {
                    this.filters.metier_id = id;
                    this.filters.employer_id = null;
                    this.refreshList();
                },

                setEmployer(id) {
                    this.filters.employer_id = id;
                    this.filters.metier_id = null;
                    this.refreshList();
                },

                refreshList() {
                    this.page = 1;
                    this.noMoreData = false;
                    this.updateUrl();
                    
                    const url = new URL(window.location.origin + window.location.pathname);
                    Object.keys(this.filters).forEach(key => {
                        if (this.filters[key]) url.searchParams.append(key, this.filters[key]);
                    });
                    url.searchParams.append('partial', '1');

                    fetch(url.toString())
                        .then(res => res.text())
                        .then(html => {
                            document.getElementById('offers-container').innerHTML = html;
                            document.getElementById('offers-scroll-container').scrollTop = 0;
                        });
                },

                loadMore() {
                    if (this.loadingMore || this.noMoreData) return;
                    
                    this.loadingMore = true;
                    this.page++;
                    
                    const url = new URL(window.location.origin + window.location.pathname);
                    Object.keys(this.filters).forEach(key => {
                        if (this.filters[key]) url.searchParams.append(key, this.filters[key]);
                    });
                    url.searchParams.append('page', this.page);
                    url.searchParams.append('partial', '1');

                    fetch(url.toString())
                        .then(res => res.text())
                        .then(html => {
                            if (!html.trim()) {
                                this.noMoreData = true;
                            } else {
                                document.getElementById('offers-container').insertAdjacentHTML('beforeend', html);
                            }
                            this.loadingMore = false;
                        });
                },

                updateUrl() {
                    const url = new URL(window.location.origin + window.location.pathname);
                    Object.keys(this.filters).forEach(key => {
                        if (this.filters[key]) url.searchParams.append(key, this.filters[key]);
                    });
                    window.history.pushState({}, '', url.toString());
                }
            }
        }
    </script>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
    </style>
</x-app-layout>
