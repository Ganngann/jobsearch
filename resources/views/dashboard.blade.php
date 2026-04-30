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
                                    <div class="flex items-center gap-8 pl-6 md:border-l border-slate-100">
                                        @if($score !== null)
                                            <div class="text-center">
                                                <p class="text-4xl font-black {{ $score >= 70 ? 'text-emerald-500' : ($score >= 40 ? 'text-amber-500' : 'text-slate-400') }}">
                                                    {{ $score }}<span class="text-lg">%</span>
                                                </p>
                                                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mt-1">Match Score</p>
                                            </div>
                                        @else
                                            <form action="{{ route('jobs.match', $offer) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="group/btn relative px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-600 hover:text-white transition-all duration-300 border border-indigo-100">
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
</x-app-layout>
