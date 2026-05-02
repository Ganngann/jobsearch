<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="font-black text-2xl text-slate-900 leading-tight">
                    {{ __('Toutes les Offres') }}
                </h2>
                <p class="text-sm text-slate-500 mt-1 font-medium">
                    {{ $jobOffers->total() }} offre(s) disponibles sur le marché
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('forem.search') }}" class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 text-white rounded-2xl text-xs font-black hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    Rechercher sur le Forem
                </a>
                <div class="px-4 py-2 bg-white border border-slate-200 rounded-2xl shadow-sm text-xs font-bold text-slate-600 flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                    Profil : {{ Auth::user()->name }}
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Barre de Filtres -->
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 p-2 mb-10">
                <form action="{{ route('dashboard') }}" method="GET" class="flex flex-col md:flex-row items-center gap-2">
                    <!-- Recherche -->
                    <div class="relative flex-1 w-full md:w-auto">
                        <div class="absolute inset-y-0 left-6 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher par titre ou employeur..." class="w-full pl-14 pr-6 py-4 bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-700 placeholder:text-slate-400 placeholder:font-medium">
                    </div>

                    <div class="hidden md:block w-px h-8 bg-slate-100"></div>

                    <!-- Filtre Contrat -->
                    <div class="w-full md:w-64">
                        <select name="contract" class="w-full px-6 py-4 bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-700 appearance-none cursor-pointer">
                            <option value="">Tous les contrats</option>
                            @foreach($contractTypes as $type)
                                <option value="{{ $type }}" {{ request('contract') == $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="hidden md:block w-px h-8 bg-slate-100"></div>

                    <!-- Filtre Score -->
                    <div class="w-full md:w-48 border-r border-slate-100">
                        <select name="min_score" class="w-full px-6 py-4 bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-700 appearance-none cursor-pointer">
                            <option value="">Match min.</option>
                            <option value="0" {{ request('min_score') == '0' ? 'selected' : '' }}>Tout match (inc. 0%)</option>
                            <option value="90" {{ request('min_score') == '90' ? 'selected' : '' }}>90% +</option>
                            <option value="75" {{ request('min_score') == '75' ? 'selected' : '' }}>75% +</option>
                            <option value="50" {{ request('min_score') == '50' ? 'selected' : '' }}>50% +</option>
                            <option value="25" {{ request('min_score') == '25' ? 'selected' : '' }}>25% +</option>
                            <option value="10" {{ request('min_score') == '10' ? 'selected' : '' }}>10% +</option>
                        </select>
                    </div>

                    <!-- Tri -->
                    <div class="w-full md:w-48">
                        <select name="sort_by" class="w-full px-6 py-4 bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-700 appearance-none cursor-pointer">
                            <option value="date_desc" {{ request('sort_by') == 'date_desc' ? 'selected' : '' }}>Plus récent</option>
                            <option value="score_desc" {{ request('sort_by') == 'score_desc' ? 'selected' : '' }}>Meilleur match</option>
                            <option value="title_asc" {{ request('sort_by') == 'title_asc' ? 'selected' : '' }}>Titre (A-Z)</option>
                        </select>
                    </div>

                    <!-- Bouton Appliquer -->
                    <button type="submit" class="w-full md:w-auto px-10 py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-[2rem] text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-indigo-100 flex items-center justify-center gap-3">
                        Filtrer
                    </button>

                    @if(request()->anyFilled(['search', 'contract', 'min_score']))
                        <a href="{{ route('dashboard') }}" class="w-full md:w-auto px-6 py-4 text-slate-400 hover:text-rose-500 text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center">
                            Effacer
                        </a>
                    @endif
                </form>
            </div>
            @if(session('status'))
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl text-sm font-bold shadow-sm flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    {{ session('status') }}
                </div>
            @endif

            @if($jobOffers->isEmpty())
                <div class="bg-white rounded-3xl border-2 border-dashed border-slate-200 p-20 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-slate-50 text-slate-300 mb-6">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Aucune offre pour le moment</h3>
                    <p class="mt-2 text-slate-500 max-w-xs mx-auto">Lancez une synchronisation pour importer des offres.</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-4">
                    @foreach($jobOffers as $offer)
                        @php
                            $match = $offer->matches->first();
                            $score = $match ? ($match->final_score ?? $match->pre_score) : null;
                        @endphp
                        <div class="group relative bg-white border border-slate-200 rounded-3xl p-1 transition-all duration-300 hover:shadow-2xl hover:shadow-slate-200 hover:-translate-y-1">
                            <div class="p-6">
                                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                                    
                                    <div class="flex items-center gap-6 flex-1">
                                        <!-- Logo Container -->
                                        <div class="shrink-0">
                                            @if($offer->employer->logo_base64 && strlen($offer->employer->logo_base64) > 100)
                                                <div class="w-20 h-20 rounded-2xl bg-slate-50 border border-slate-100 p-3 flex items-center justify-center">
                                                    <img src="data:{{ $offer->employer->logo_mime_type }};base64,{{ $offer->employer->logo_base64 }}" class="w-full h-full object-contain" alt="Logo">
                                                </div>
                                            @else
                                                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center text-slate-400 border border-slate-100">
                                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-7h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Info Container -->
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-500">{{ $offer->employer->label }}</span>
                                                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ $offer->location }}</span>
                                                
                                                @if($offer->metier)
                                                    <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                                    <div class="flex items-center gap-1">
                                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $offer->metier->label }}</span>
                                                        
                                                        @if(Auth::user()->preferredMetiers->contains($offer->metier->id))
                                                            <span class="text-rose-500" title="Dans vos métiers favoris">
                                                                <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                                            </span>
                                                        @else
                                                            <button 
                                                                onclick="addMetier({{ $offer->metier->id }})"
                                                                class="p-1 text-slate-300 hover:text-rose-500 transition-colors"
                                                                title="Ajouter aux métiers favoris"
                                                            >
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                                            </button>
                                                        @endif

                                                        <button 
                                                            onclick="blacklistMetier({{ $offer->metier->id }}, '{{ addslashes($offer->metier->label) }}')"
                                                            class="p-1 text-slate-300 hover:text-rose-500 transition-colors"
                                                            title="Blacklister ce métier"
                                                        >
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                            <h3 class="text-xl font-black text-slate-900 leading-tight group-hover:text-indigo-600 transition-colors">
                                                <a href="{{ route('jobs.show', $offer) }}">
                                                    {{ $offer->title }}
                                                </a>
                                            </h3>
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                <span class="px-3 py-1 rounded-lg bg-slate-50 text-slate-600 text-[10px] font-black uppercase tracking-tighter border border-slate-100">
                                                    {{ $offer->contract_type }}
                                                </span>
                                                <span class="px-3 py-1 rounded-lg bg-slate-50 text-slate-600 text-[10px] font-black uppercase tracking-tighter border border-slate-100">
                                                    {{ $offer->working_regime }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Score/Action Container -->
                                    <div class="flex items-center gap-6 pl-6 md:border-l border-slate-100">
                                        @if($match)
                                            <!-- Score Data -->
                                            <div class="text-center">
                                                <p class="text-3xl font-black {{ $match->pre_score >= 70 ? 'text-emerald-500' : ($match->pre_score >= 40 ? 'text-amber-500' : 'text-slate-400') }}">
                                                    {{ $match->pre_score }}<span class="text-xs">%</span>
                                                </p>
                                                <p class="text-[8px] font-black uppercase tracking-widest text-slate-400 mt-1">Data Match</p>
                                            </div>

                                            @if($match->final_score !== null)
                                                <!-- Score IA -->
                                                <div class="text-center px-4 border-l border-slate-50">
                                                    <p class="text-3xl font-black text-indigo-600">
                                                        {{ $match->final_score }}<span class="text-xs">%</span>
                                                    </p>
                                                    <p class="text-[8px] font-black uppercase tracking-widest text-indigo-400 mt-1">IA Match</p>
                                                </div>
                                            @else
                                                <!-- Bouton Analyse IA -->
                                                <form action="{{ route('jobs.match', $offer) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-2 bg-indigo-50 text-indigo-600 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-indigo-600 hover:text-white transition-all border border-indigo-100" title="Demander une analyse sémantique par IA">
                                                        Analyse IA
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <form action="{{ route('jobs.match', $offer) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="px-4 py-2 bg-slate-50 text-slate-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-900 hover:text-white transition-all border border-slate-200">
                                                    Calculer Match
                                                </button>
                                            </form>
                                        @endif
                                        
                                        <a href="{{ route('jobs.show', $offer) }}" class="p-4 rounded-2xl bg-slate-900 text-white hover:bg-indigo-600 transition-all duration-300 shadow-xl shadow-slate-200 hover:shadow-indigo-200">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                                        </a>
                                    </div>

                                </div>
                            </div>
                            
                            <!-- Progress Bar Subtile -->
                            @if($score !== null)
                                <div class="absolute bottom-0 left-6 right-6 h-0.5 bg-slate-50 rounded-full overflow-hidden">
                                    <div class="h-full {{ $score >= 70 ? 'bg-emerald-500' : ($score >= 40 ? 'bg-amber-500' : 'bg-slate-300') }}" style="width: {{ $score }}%"></div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-10">
                    {{ $jobOffers->links() }}
                </div>
            @endif

        </div>
    </div>
    <script>
        function addMetier(id) {
            fetch(`/profile/metiers/${id}/add`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            }).then(() => window.location.reload());
        }

        function blacklistMetier(id, label) {
            fetch(`/profile/metiers/${id}/blacklist`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            }).then(() => window.location.reload());
        }
    </script>
</x-app-layout>
