<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Langues & Communication') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Indiquez les langues que vous maîtrisez et votre niveau d'aisance.") }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.languages.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($allLanguages as $language)
                <div class="flex flex-col p-3 border border-gray-200 rounded-lg bg-white shadow-sm">
                    <label class="flex items-center cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="languages[]" 
                            value="{{ $language->id }}" 
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            {{ $user->languages->contains($language->id) ? 'checked' : '' }}
                            @change="$el.closest('.flex-col').querySelector('.lang-level-select').disabled = !$el.checked"
                        >
                        <span class="ml-2 text-sm font-medium text-gray-700">{{ $language->label }} ({{ $language->code }})</span>
                    </label>
                    
                    <div class="mt-2 pl-6">
                        <x-text-input 
                            name="lang_levels[{{ $language->id }}]" 
                            type="text"
                            placeholder="ex: C1, Natif, Bonnes notions..."
                            class="lang-level-select mt-1 block w-full text-xs"
                            :value="$user->languages->find($language->id)->pivot->level ?? ''"
                            :disabled="!$user->languages->contains($language->id)"
                        />
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Enregistrer les langues') }}</x-primary-button>

            @if (session('status') === 'languages-updated')
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
