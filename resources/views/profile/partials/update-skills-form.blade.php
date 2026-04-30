<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Compétences (Hard & Soft Skills)') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Sélectionnez vos compétences techniques et transversales. L'IA utilisera ces informations pour le matching.") }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.skills.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div x-data="{ search: '' }" class="space-y-4">
            <div class="relative">
                <x-text-input 
                    x-model="search" 
                    type="text" 
                    placeholder="Rechercher une compétence..." 
                    class="w-full"
                />
            </div>

            <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-md p-4 bg-gray-50">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($allSkills as $skill)
                        <div 
                            x-show="search === '' || '{{ strtolower($skill->label) }}'.includes(search.toLowerCase())"
                            class="flex flex-col p-3 bg-white border border-gray-100 rounded-lg shadow-sm hover:border-indigo-300 transition-colors"
                        >
                            <label class="flex items-center cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="skills[]" 
                                    value="{{ $skill->id }}" 
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    {{ $user->skills->contains($skill->id) ? 'checked' : '' }}
                                    @change="$el.closest('.flex-col').querySelector('.level-select').disabled = !$el.checked"
                                >
                                <span class="ml-2 text-sm font-medium text-gray-700">{{ $skill->label }}</span>
                                <span class="ml-auto text-xs font-semibold px-2 py-0.5 rounded-full {{ $skill->type === 'hard' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                    {{ $skill->type }}
                                </span>
                            </label>
                            
                            <div class="mt-2 pl-6">
                                <select 
                                    name="levels[{{ $skill->id }}]" 
                                    class="level-select mt-1 block w-full text-xs border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    {{ $user->skills->contains($skill->id) ? '' : 'disabled' }}
                                >
                                    <option value="">{{ __('Sélectionner un niveau') }}</option>
                                    <option value="beginner" {{ ($user->skills->find($skill->id)->pivot->level ?? '') === 'beginner' ? 'selected' : '' }}>Débutant</option>
                                    <option value="intermediate" {{ ($user->skills->find($skill->id)->pivot->level ?? '') === 'intermediate' ? 'selected' : '' }}>Intermédiaire</option>
                                    <option value="advanced" {{ ($user->skills->find($skill->id)->pivot->level ?? '') === 'advanced' ? 'selected' : '' }}>Avancé</option>
                                    <option value="expert" {{ ($user->skills->find($skill->id)->pivot->level ?? '') === 'expert' ? 'selected' : '' }}>Expert</option>
                                </select>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Enregistrer les compétences') }}</x-primary-button>

            @if (session('status') === 'skills-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Enregistré.') }}</p>
            @endif
        </div>
    </form>
</section>
