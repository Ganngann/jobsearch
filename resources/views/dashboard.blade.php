<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Mes Correspondances Forem') }}
            </h2>
            <span class="text-sm text-gray-500">
                Basé sur votre profil : <span class="font-medium text-indigo-600">{{ Auth::user()->location }}</span>
            </span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if($matches->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 text-gray-400 mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Aucune offre trouvée</h3>
                    <p class="mt-1 text-sm text-gray-500">Essayez de synchroniser les offres ou de compléter votre profil.</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-6">
                    @foreach($matches as $match)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200">
                            <div class="p-6">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center">
                                        @if($match->jobOffer->employer->logo_base64)
                                            <img src="data:{{ $match->jobOffer->employer->logo_mime_type }};base64,{{ $match->jobOffer->employer->logo_base64 }}" class="w-12 h-12 rounded bg-gray-50 object-contain mr-4" alt="Logo">
                                        @else
                                            <div class="w-12 h-12 rounded bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold mr-4">
                                                {{ substr($match->jobOffer->employer->label, 0, 1) }}
                                            </div>
                                        @endif
                                        <div>
                                            <h3 class="text-lg font-bold text-gray-900 leading-tight">
                                                <a href="{{ route('jobs.show', $match->jobOffer) }}" class="hover:text-indigo-600">
                                                    {{ $match->jobOffer->title }}
                                                </a>
                                            </h3>
                                            <p class="text-sm text-gray-600">
                                                {{ $match->jobOffer->employer->label }} • {{ $match->jobOffer->location }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <div class="inline-flex flex-col items-center">
                                            <span class="text-2xl font-black {{ $match->final_score >= 70 ? 'text-green-600' : ($match->final_score >= 40 ? 'text-orange-500' : 'text-gray-400') }}">
                                                {{ $match->final_score ?? $match->pre_score }}%
                                            </span>
                                            <span class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Match Score</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $match->jobOffer->contract_type }}
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $match->jobOffer->working_regime }}
                                    </span>
                                    @if($match->jobOffer->metier)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">
                                            {{ $match->jobOffer->metier->label }}
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-6">
                                    <div class="relative w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="absolute top-0 left-0 h-full {{ $match->final_score >= 70 ? 'bg-green-500' : ($match->final_score >= 40 ? 'bg-orange-400' : 'bg-gray-300') }}" style="width: {{ $match->final_score ?? $match->pre_score }}%"></div>
                                    </div>
                                    <div class="mt-2 flex justify-between items-center text-xs text-gray-500">
                                        <span>Analyse IA : {{ $match->analyzed_at ? 'Terminée' : 'Statique uniquement' }}</span>
                                        <a href="{{ route('jobs.show', $match->jobOffer) }}" class="font-medium text-indigo-600 hover:text-indigo-500 flex items-center">
                                            Détails & Analyse
                                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $matches->links() }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
