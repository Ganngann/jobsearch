<x-app-layout>
    <div class="min-h-screen bg-[#f8fafc] py-12">
        <div class="w-full max-w-[1600px] mx-auto px-6 lg:px-12">
            
            <form id="search-form" x-data="{}" x-ref="searchForm" action="{{ route('forem.search') }}" method="GET" class="w-full">
                <!-- Header & Search Bar -->
                <div class="mb-12 w-full">
                    <div class="flex items-center justify-between mb-8">
                        <h1 class="text-5xl font-black text-slate-900 tracking-tight">Recherche Forem Live</h1>
                        @if(!empty($activeFilters))
                            <a href="{{ route('forem.search', ['q' => $query]) }}" class="px-4 py-2 rounded-xl bg-indigo-50 text-xs font-black uppercase tracking-widest text-indigo-600 hover:bg-indigo-100 transition-all">
                                Réinitialiser les filtres
                            </a>
                        @endif
                    </div>
                    
                    <div class="relative w-full group">
                        <div class="absolute inset-y-0 left-8 flex items-center pointer-events-none">
                            <svg class="w-8 h-8 text-slate-400 group-focus-within:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" name="q" value="{{ $query }}" placeholder="Métier, entreprise, mots-clés..." 
                               class="w-full pl-20 pr-40 py-8 bg-white border-none rounded-[2.5rem] shadow-2xl shadow-slate-200/60 focus:ring-4 focus:ring-indigo-100 transition-all text-xl font-medium text-slate-700">
                        <button type="submit" class="absolute right-4 top-4 bottom-4 px-10 bg-indigo-600 text-white rounded-[2rem] font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200 text-lg">
                            Rechercher
                        </button>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row gap-12 items-start w-full">
                    
                    <!-- Sidebar: Dynamic Filters (Largeur fixe sur desktop) -->
                    <div class="w-full lg:w-[350px] shrink-0 space-y-6">
                        <div class="flex items-center gap-2 mb-2 px-2">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                            <span class="text-sm font-black uppercase tracking-widest text-slate-400">Filtres</span>
                        </div>

                        @foreach($filterData as $section)
                            @if(count($section['criteres']) > 0)
                            <div x-data="{ open: true, showAll: false, search: '' }" class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/60 transition-all hover:shadow-md">
                                <button type="button" @click="open = !open" class="w-full flex items-center justify-between group">
                                    <h3 class="text-[11px] font-black uppercase tracking-widest text-slate-500 group-hover:text-slate-900 transition-colors">{{ $section['libelle'] }}</h3>
                                    <svg class="w-4 h-4 text-slate-300 transition-transform duration-300" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                                </button>

                                <div x-show="open" x-collapse x-cloak class="mt-6">
                                    @if(count($section['criteres']) > 10)
                                        <div class="mb-4 relative">
                                            <input type="text" x-model="search" placeholder="Chercher dans {{ strtolower($section['libelle']) }}..." 
                                                   class="w-full px-4 py-3 bg-slate-50 border-none rounded-xl text-xs font-bold text-slate-600 focus:ring-2 focus:ring-indigo-100 placeholder:text-slate-300">
                                        </div>
                                    @endif

                                    <div class="space-y-1.5 max-h-[350px] overflow-y-auto pr-2 custom-scrollbar">
                                        @foreach($section['criteres'] as $index => $critere)
                                            <label class="flex items-start gap-3 p-2 rounded-xl hover:bg-slate-50 group cursor-pointer transition-colors" 
                                                   x-show="(showAll || {{ $index }} < 8) && (search === '' || '{{ strtolower(addslashes($critere['libelle'])) }}'.includes(search.toLowerCase()))">
                                                <div class="relative flex items-center mt-0.5">
                                                    <input type="checkbox" 
                                                           name="filters[{{ $section['nom'] }}][]" 
                                                           value="{{ $critere['guid'] }}" 
                                                           @change="document.getElementById('search-form').submit()"
                                                           @if(isset($activeFilters[$section['nom']]) && in_array($critere['guid'], $activeFilters[$section['nom']])) checked @endif
                                                           class="w-5 h-5 rounded-lg border-slate-200 text-indigo-600 focus:ring-indigo-500 transition-all cursor-pointer">
                                                </div>
                                                <div class="flex-1 flex items-center justify-between min-w-0">
                                                    <span class="text-sm font-bold text-slate-600 group-hover:text-indigo-600 transition-colors truncate pr-4">
                                                        {{ $critere['libelle'] }}
                                                    </span>
                                                    <span class="shrink-0 px-2 py-0.5 rounded-lg bg-slate-100 text-[10px] font-black text-slate-400 group-hover:bg-white group-hover:text-indigo-500 transition-all">
                                                        {{ number_format($critere['count'] ?? 0, 0, ',', ' ') }}
                                                    </span>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>

                                    @if(count($section['criteres']) > 8)
                                        <button type="button" @click="showAll = !showAll" 
                                                class="mt-4 w-full py-2 text-[10px] font-black uppercase tracking-widest text-indigo-600 hover:text-indigo-800 transition-colors border-t border-slate-100 pt-4"
                                                x-text="showAll ? 'Voir moins' : 'Voir plus ({{ count($section['criteres']) - 8 }})'">
                                        </button>
                                    @endif
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>

                    <!-- Results List -->
                    <div class="flex-1 min-w-0 w-full">
                        <div class="flex items-center justify-between mb-8 px-4 bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="px-4 py-2 bg-indigo-600 rounded-xl text-white font-black text-lg">
                                    {{ number_format($total, 0, ',', ' ') }}
                                </div>
                                <span class="text-sm font-black text-slate-400 uppercase tracking-widest text-[11px]">Offres disponibles</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Trier par :</span>
                                <select class="bg-slate-50 border-none rounded-xl px-4 py-2 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-100 cursor-pointer">
                                    <option>Plus récentes</option>
                                    <option>Pertinence</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-6">
                            @forelse($offers as $offer)
                                <div class="bg-white rounded-[2.5rem] p-10 shadow-sm border border-slate-100 hover:border-indigo-200 hover:shadow-2xl hover:shadow-indigo-500/10 transition-all group relative overflow-hidden">
                                    <div class="absolute top-0 left-0 w-2 h-full bg-indigo-600 transform -translate-x-full group-hover:translate-x-0 transition-transform"></div>
                                    
                                    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-8">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-wrap items-center gap-3 mb-6">
                                                @if(in_array($offer['idOffreEmploi'], $existingJobIds))
                                                    <span class="px-4 py-1.5 rounded-full bg-emerald-50 text-emerald-600 text-[11px] font-black uppercase tracking-widest border border-emerald-100 flex items-center gap-2">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                                        Déjà importé
                                                    </span>
                                                @endif
                                                <span class="px-4 py-1.5 rounded-full bg-indigo-50 text-indigo-600 text-[11px] font-black uppercase tracking-widest">
                                                    {{ $offer['typeContrat'] ?? 'N/A' }}
                                                </span>
                                                <span class="px-4 py-1.5 rounded-full bg-slate-50 text-slate-500 text-[11px] font-black uppercase tracking-widest">
                                                    {{ $offer['tempsTravail'] ?? 'N/A' }}
                                                </span>
                                                <div class="flex items-center gap-4 ml-auto">
                                                    <span class="text-xs font-bold text-slate-400 flex items-center gap-1.5">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                        {{ $offer['publication'] ?? 'N/A' }}
                                                    </span>
                                                    <span class="text-xs font-bold text-slate-300">Réf: {{ $offer['idOffreEmploi'] }}</span>
                                                </div>
                                            </div>
                                            
                                            <h2 class="text-2xl font-black text-slate-900 group-hover:text-indigo-600 transition-colors mb-4 leading-tight">
                                                {{ $offer['titreOffre'] }}
                                            </h2>
                                            
                                            <div class="flex flex-wrap items-center gap-8 text-sm font-bold text-slate-500">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-all">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-7h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                                    </div>
                                                    <span class="text-slate-700">{{ $offer['nomEmployeur'] }}</span>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-all">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                                    </div>
                                                    <span>{{ implode(', ', $offer['lieuxTravail'] ?? []) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="shrink-0 xl:pl-8 border-l border-slate-100 flex flex-col items-center gap-4">
                                            <a href="{{ route('jobs.show', ['jobOffer' => $offer['idOffreEmploi']]) }}" 
                                               class="w-full xl:w-auto inline-flex items-center justify-center gap-3 px-10 py-5 bg-slate-900 text-white rounded-2xl font-black hover:bg-indigo-600 transition-all shadow-xl shadow-slate-200 hover:-translate-y-1">
                                                Voir l'offre
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="bg-white rounded-[3rem] p-24 text-center border-2 border-dashed border-slate-200">
                                    <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-8 text-slate-300">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    </div>
                                    <h3 class="text-2xl font-black text-slate-800 mb-3">Aucun résultat trouvé</h3>
                                    <p class="text-slate-500 font-medium">Nous n'avons pas trouvé d'offres correspondant à vos critères actuels.</p>
                                    <div class="mt-10">
                                        <a href="{{ route('forem.search') }}" class="px-8 py-4 bg-indigo-600 text-white rounded-2xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                                            Effacer tous les filtres
                                        </a>
                                    </div>
                                </div>
                            @endforelse

                            <!-- Pagination -->
                            @if($total > $rows)
                            <div class="pt-16 flex justify-center items-center gap-8">
                                @if($page > 1)
                                    <a href="{{ route('forem.search', array_merge(request()->all(), ['page' => $page - 1])) }}" class="flex items-center gap-3 px-8 py-4 bg-white border border-slate-200 rounded-2xl font-black text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 transition-all group">
                                        <svg class="w-5 h-5 group-hover:-translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                                        Page précédente
                                    </a>
                                @endif
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-black text-slate-900">Page {{ $page }}</span>
                                    <span class="text-sm font-bold text-slate-400">sur {{ ceil($total / $rows) }}</span>
                                </div>
                                <a href="{{ route('forem.search', array_merge(request()->all(), ['page' => $page + 1])) }}" class="flex items-center gap-3 px-8 py-4 bg-white border border-slate-200 rounded-2xl font-black text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 transition-all group">
                                    Page suivante
                                    <svg class="w-5 h-5 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
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
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f8fafc; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
    </style>
</x-app-layout>
