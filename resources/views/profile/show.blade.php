<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Mon Profil Professionnel') }}
            </h2>
            <x-nav-link :href="route('profile.edit')" :active="false">
                <span class="flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    {{ __('Modifier') }}
                </span>
            </x-nav-link>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Header Profile Card --}}
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden mb-8 border border-gray-100">
                <div class="h-32 bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500"></div>
                <div class="px-8 pb-8">
                    <div class="relative flex items-end -mt-16 mb-6">
                        <div class="p-1 bg-white rounded-2xl shadow-lg">
                            <div class="w-32 h-32 bg-gray-200 rounded-xl flex items-center justify-center text-indigo-600 text-4xl font-bold border-2 border-indigo-50">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                        </div>
                        <div class="ml-6 mb-2">
                            <h1 class="text-3xl font-bold text-gray-900">{{ $user->name }}</h1>
                            <p class="text-indigo-600 font-medium text-lg">{{ $user->headline ?? __('Candidat en recherche active') }}</p>
                        </div>
                        <div class="ml-auto mb-2 flex space-x-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"></path></svg>
                                Disponible
                            </span>
                            @if($user->location)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                {{ $user->location }}
                            </span>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-8">
                        {{-- Left Column: Bio & Aspirations --}}
                        <div class="lg:col-span-2 space-y-8">
                            <section>
                                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                    <span class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center mr-2 text-sm">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    </span>
                                    {{ __('Récit de Vie / Dimension Humaine') }}
                                </h3>
                                <div class="prose prose-indigo max-w-none text-gray-700 leading-relaxed bg-indigo-50/30 p-6 rounded-2xl border border-indigo-50">
                                    @if($user->profile_text)
                                        {!! nl2br(e($user->profile_text)) !!}
                                    @else
                                        <p class="italic text-gray-400">{{ __('Aucun récit complété pour le moment. Racontez votre histoire pour attirer les recruteurs !') }}</p>
                                    @endif
                                </div>
                            </section>

                            <section>
                                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                    <span class="w-8 h-8 bg-pink-100 text-pink-600 rounded-lg flex items-center justify-center mr-2 text-sm">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                    </span>
                                    {{ __('Valeurs & Aspirations') }}
                                </h3>
                                <div class="bg-pink-50/30 p-6 rounded-2xl border border-pink-50 text-gray-700">
                                    @if($user->aspirations)
                                        {!! nl2br(e($user->aspirations)) !!}
                                    @else
                                        <p class="italic text-gray-400">{{ __('Quelles sont vos valeurs ? Qu\'attendez-vous d\'une entreprise ?') }}</p>
                                    @endif
                                </div>
                            </section>
                        </div>

                        {{-- Right Column: Skills & Details --}}
                        <div class="space-y-8">
                            <section>
                                <h3 class="text-lg font-bold text-gray-900 mb-4">{{ __('Hard Skills') }}</h3>
                                <div class="flex flex-wrap gap-2">
                                    @forelse($user->skills->where('type', 'hard') as $skill)
                                        <div class="group relative">
                                            <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-blue-50 text-blue-700 border border-blue-100 transition-all hover:bg-blue-600 hover:text-white cursor-default">
                                                {{ $skill->label }}
                                                @if($skill->pivot->level)
                                                    <span class="ml-1 text-[10px] uppercase font-bold opacity-75">({{ $skill->pivot->level }})</span>
                                                @endif
                                            </span>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-400 italic">{{ __('Aucune compétence technique répertoriée.') }}</p>
                                    @endforelse
                                </div>
                            </section>

                            <section>
                                <h3 class="text-lg font-bold text-gray-900 mb-4">{{ __('Soft Skills') }}</h3>
                                <div class="flex flex-wrap gap-2">
                                    @forelse($user->skills->where('type', 'soft') as $skill)
                                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-purple-50 text-purple-700 border border-purple-100">
                                            {{ $skill->label }}
                                        </span>
                                    @empty
                                        <p class="text-sm text-gray-400 italic">{{ __('Aucune soft skill répertoriée.') }}</p>
                                    @endforelse
                                </div>
                            </section>

                            <section>
                                <h3 class="text-lg font-bold text-gray-900 mb-4">{{ __('Langues') }}</h3>
                                <div class="space-y-2">
                                    @forelse($user->languages as $language)
                                        <div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg">
                                            <span class="text-sm font-medium text-gray-700">{{ $language->label }}</span>
                                            <span class="text-xs font-bold text-indigo-600 uppercase">{{ $language->pivot->level ?? 'N/A' }}</span>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-400 italic">{{ __('Non spécifié.') }}</p>
                                    @endforelse
                                </div>
                            </section>

                            <section>
                                <h3 class="text-lg font-bold text-gray-900 mb-4">{{ __('Permis & Mobilité') }}</h3>
                                <div class="flex flex-wrap gap-2">
                                    @forelse($user->permits as $permit)
                                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-yellow-50 text-yellow-800 border border-yellow-100">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
                                            {{ $permit->label }}
                                        </span>
                                    @empty
                                        <p class="text-sm text-gray-400 italic">{{ __('Non spécifié.') }}</p>
                                    @endforelse
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
