<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mon Parcours (Couche 1)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Section Expériences -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                {{ __('Expériences Professionnelles') }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ __("Ajoutez vos postes passés et actuels pour donner du contexte à l'IA.") }}
                            </p>
                        </header>

                        <div class="mt-6 space-y-4">
                            @foreach($experiences as $experience)
                                <div class="flex justify-between items-start p-4 rounded-lg border {{ $experience->status === 'draft' ? 'bg-amber-50 border-amber-200 animate-pulse' : 'bg-gray-50 border-gray-100' }}">
                                    <div>
                                        @if($experience->status === 'draft')
                                            <span class="text-[9px] font-bold text-amber-600 uppercase tracking-tighter bg-amber-100 px-1.5 py-0.5 rounded mb-1 inline-block">Suggéré par l'IA</span>
                                        @endif
                                        <h3 class="font-bold text-gray-900">{{ $experience->title }}</h3>
                                        <p class="text-indigo-600 font-medium">{{ $experience->company }}</p>
                                        <p class="text-xs text-gray-500">
                                            {{ $experience->start_date ? $experience->start_date->format('M Y') : '?' }} - 
                                            {{ $experience->is_current ? 'Aujourd\'hui' : ($experience->end_date ? $experience->end_date->format('M Y') : '?') }}
                                        </p>
                                        @if($experience->description)
                                            <p class="mt-2 text-sm text-gray-700">{{ $experience->description }}</p>
                                        @endif
                                    </div>
                                    <div class="flex flex-col items-end gap-2">
                                        @if($experience->status === 'draft')
                                            <form action="{{ route('profile.journey.experience.validate', $experience) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="bg-amber-600 text-white px-3 py-1 rounded-md text-xs font-bold hover:bg-amber-700 shadow-sm">Valider</button>
                                            </form>
                                        @endif
                                        <form action="{{ route('profile.journey.experience.delete', $experience) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-[10px]">Supprimer</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach

                            <form method="post" action="{{ route('profile.journey.experience.store') }}" class="mt-6 space-y-4 bg-indigo-50 p-6 rounded-xl border border-indigo-100">
                                @csrf
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="company" :value="__('Entreprise')" />
                                        <x-text-input id="company" name="company" type="text" class="mt-1 block w-full" required />
                                    </div>
                                    <div>
                                        <x-input-label for="title" :value="__('Poste')" />
                                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" required />
                                    </div>
                                </div>

                                <div>
                                    <x-input-label for="description" :value="__('Missions / Description')" />
                                    <textarea id="description" name="description" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="2"></textarea>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="start_date" :value="__('Date de début')" />
                                        <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" required />
                                    </div>
                                    <div>
                                        <x-input-label for="end_date" :value="__('Date de fin')" />
                                        <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" />
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <input type="checkbox" id="is_current" name="is_current" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <x-input-label for="is_current" :value="__('Poste actuel')" />
                                </div>

                                <div class="flex items-center gap-4">
                                    <x-primary-button>{{ __('Ajouter l\'expérience') }}</x-primary-button>
                                </div>
                            </form>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Section Formation -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                {{ __('Formations & Diplômes') }}
                            </h2>
                        </header>

                        <div class="mt-6 space-y-4">
                            @foreach($educations as $education)
                                <div class="flex justify-between items-start p-4 rounded-lg border {{ $education->status === 'draft' ? 'bg-amber-50 border-amber-200 animate-pulse' : 'bg-gray-50 border-gray-100' }}">
                                    <div>
                                        @if($education->status === 'draft')
                                            <span class="text-[9px] font-bold text-amber-600 uppercase tracking-tighter bg-amber-100 px-1.5 py-0.5 rounded mb-1 inline-block">Suggéré par l'IA</span>
                                        @endif
                                        <h3 class="font-bold text-gray-900">{{ $education->degree }}</h3>
                                        <p class="text-indigo-600 font-medium">{{ $education->school }}</p>
                                        <p class="text-xs text-gray-500">{{ $education->graduation_year }}</p>
                                    </div>
                                    <div class="flex flex-col items-end gap-2">
                                        @if($education->status === 'draft')
                                            <form action="{{ route('profile.journey.education.validate', $education) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="bg-amber-600 text-white px-3 py-1 rounded-md text-xs font-bold hover:bg-amber-700 shadow-sm">Valider</button>
                                            </form>
                                        @endif
                                        <form action="{{ route('profile.journey.education.delete', $education) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-[10px]">Supprimer</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach

                            <form method="post" action="{{ route('profile.journey.education.store') }}" class="mt-6 space-y-4 bg-green-50 p-6 rounded-xl border border-green-100">
                                @csrf
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="school" :value="__('École / Organisme')" />
                                        <x-text-input id="school" name="school" type="text" class="mt-1 block w-full" required />
                                    </div>
                                    <div>
                                        <x-input-label for="degree" :value="__('Diplôme / Titre')" />
                                        <x-text-input id="degree" name="degree" type="text" class="mt-1 block w-full" required />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="field" :value="__('Domaine')" />
                                        <x-text-input id="field" name="field" type="text" class="mt-1 block w-full" />
                                    </div>
                                    <div>
                                        <x-input-label for="graduation_year" :value="__('Année de diplomation')" />
                                        <x-text-input id="graduation_year" name="graduation_year" type="number" class="mt-1 block w-full" />
                                    </div>
                                </div>

                                <div class="flex items-center gap-4">
                                    <x-primary-button class="bg-green-600 hover:bg-green-700">{{ __('Ajouter la formation') }}</x-primary-button>
                                </div>
                            </form>
                        </div>
                    </section>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
