<x-app-layout>
    <div class="min-h-screen bg-[#f8fafc] py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <form id="search-form" action="{{ route('forem.search') }}" method="GET">
                <!-- Header & Search Bar -->
                <div class="mb-12">
                    <div class="flex items-center justify-between mb-6">
                        <h1 class="text-4xl font-black text-slate-900 tracking-tight">Recherche Forem Live</h1>
                        @if(!empty($activeFilters))
                            <a href="{{ route('forem.search', ['q' => $query]) }}" class="text-xs font-black uppercase tracking-widest text-indigo-600 hover:text-indigo-800 transition-colors">
                                Réinitialiser les filtres
                            </a>
                        @endif
                    </div>
                    
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-6 flex items-center pointer-events-none">
                            <svg class="w-6 h-6 text-slate-400 group-focus-within:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" name="q" value="{{ $query }}" placeholder="Métier, mots-clés..." 
                               class="w-full pl-16 pr-32 py-6 bg-white border-none rounded-[2rem] shadow-xl shadow-slate-200/50 focus:ring-4 focus:ring-indigo-100 transition-all text-lg font-medium text-slate-700">
                        <button type="submit" class="absolute right-3 top-3 bottom-3 px-8 bg-indigo-600 text-white rounded-[1.5rem] font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">
                            Rechercher
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                    
                    <!-- Sidebar: Dynamic Filters -->
                    <div class="lg:col-span-3 space-y-6">
                        @foreach($filterData as $section)
                            @if(count($section['criteres']) > 0)
                            <div x-data="{ open: true, showAll: false, search: '' }" class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 transition-all">
                                <button type="button" @click="open = !open" class="w-full flex items-center justify-between mb-4 group">
                                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 group-hover:text-slate-600 transition-colors">{{ $section['libelle'] }}</h3>
                                    <svg class="w-4 h-4 text-slate-300 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                                </button>

                                <div x-show="open" x-collapse>
                                    @if(count($section['criteres']) > 10)
                                        <div class="mb-4 relative">
                                            <input type="text" x-model="search" placeholder="Filtrer..." 
                                                   class="w-full px-4 py-2 bg-slate-50 border-none rounded-xl text-xs font-bold text-slate-600 focus:ring-2 focus:ring-indigo-100">
                                        </div>
                                    @endif

                                    <div class="space-y-2 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                                        @foreach($section['criteres'] as $index => $critere)
                                            <label class="flex items-start gap-3 group cursor-pointer" 
                                                   x-show="(showAll || {{ $index }} < 6) && (search === '' || '{{ strtolower(addslashes($critere['libelle'])) }}'.includes(search.toLowerCase()))">
                                                <div class="relative flex items-center mt-0.5">
                                                    <input type="checkbox" 
                                                           name="filters[{{ $section['nom'] }}][]" 
                                                           value="{{ $critere['guid'] }}" 
                                                           onchange="this.form.submit()"
                                                           @if(isset($activeFilters[$section['nom']]) && in_array($critere['guid'], $activeFilters[$section['nom']])) checked @endif
                                                           class="w-5 h-5 rounded-lg border-slate-200 text-indigo-600 focus:ring-indigo-500 transition-all cursor-pointer">
                                                </div>
                                                <div class="flex-1 flex items-center justify-between min-w-0">
                                                    <span class="text-sm font-bold text-slate-600 group-hover:text-indigo-600 transition-colors truncate pr-2">
                                                        {{ $critere['libelle'] }}
                                                    </span>
                                                    <span class="shrink-0 px-2 py-0.5 rounded-lg bg-slate-100 text-[10px] font-black text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-500 transition-all">
                                                        {{ $critere['count'] ?? 0 }}
                                                    </span>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>

                                    @if(count($section['criteres']) > 6)
                                        <button type="button" @click="showAll = !showAll" 
                                                class="mt-4 text-[10px] font-black uppercase tracking-widest text-indigo-600 hover:text-indigo-800 transition-colors"
                                                x-text="showAll ? 'Voir moins' : 'Voir plus ({{ count($section['criteres']) - 6 }})'">
                                        </button>
                                    @endif
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>

                    <!-- Results List -->
                    <div class="lg:col-span-9">
                        <div class="flex items-center justify-between mb-8 px-4">
                            <div>
                                <span class="text-sm font-black text-slate-900">{{ number_format($total, 0, ',', ' ') }}</span>
                                <span class="text-sm font-bold text-slate-400 ml-1 uppercase tracking-widest text-[10px]">opportunités trouvées</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Trier par :</span>
                                <select class="bg-transparent border-none text-sm font-bold text-slate-700 focus:ring-0 cursor-pointer">
                                    <option>Plus récentes</option>
                                    <option>Pertinence</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-4">
                            @forelse($offers as $offer)
                                <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-slate-100 hover:border-indigo-200 hover:shadow-xl hover:shadow-indigo-500/5 transition-all group">
                                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-4">
                                                <span class="px-3 py-1 rounded-lg bg-indigo-50 text-indigo-600 text-[10px] font-black uppercase tracking-widest">
                                                    {{ $offer['typeContrat'] ?? 'N/A' }}
                                                </span>
                                                <span class="text-[10px] font-bold text-slate-400">#{{ $offer['idOffreEmploi'] }}</span>
                                            </div>
                                            <h2 class="text-xl font-black text-slate-900 group-hover:text-indigo-600 transition-colors mb-2">
                                                {{ $offer['titreOffre'] }}
                                            </h2>
                                            <div class="flex items-center gap-6 text-sm font-bold text-slate-500">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-300">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-7h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                                    </div>
                                                    {{ $offer['nomEmployeur'] }}
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-300">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                                    </div>
                                                    {{ implode(', ', $offer['lieuxTravail'] ?? []) }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="shrink-0">
                                            <a href="{{ route('jobs.show', ['jobOffer' => $offer['idOffreEmploi']]) }}" 
                                               class="inline-flex items-center gap-3 px-8 py-4 bg-slate-900 text-white rounded-2xl font-bold hover:bg-indigo-600 transition-all shadow-lg shadow-slate-200 hover:-translate-y-0.5">
                                                Voir le détail
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="bg-white rounded-[2rem] p-16 text-center border-2 border-dashed border-slate-200">
                                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-300">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    </div>
                                    <h3 class="text-xl font-bold text-slate-800 mb-2">Aucun résultat</h3>
                                    <p class="text-slate-500">Essayez de modifier vos mots-clés ou filtres.</p>
                                </div>
                            @endforelse

                            <!-- Pagination -->
                            @if($total > $rows)
                            <div class="pt-12 flex justify-center items-center gap-6">
                                @if($page > 1)
                                    <a href="{{ route('forem.search', array_merge(request()->all(), ['page' => $page - 1])) }}" class="flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 rounded-xl font-bold text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 transition-all group">
                                        <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                                        Précédent
                                    </a>
                                @endif
                                <span class="text-xs font-black uppercase tracking-widest text-slate-400">Page {{ $page }}</span>
                                <a href="{{ route('forem.search', array_merge(request()->all(), ['page' => $page + 1])) }}" class="flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 rounded-xl font-bold text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 transition-all group">
                                    Suivant
                                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
    </style>
</x-app-layout>
