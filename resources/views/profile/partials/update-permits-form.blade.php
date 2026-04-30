<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Permis de Conduire & Mobilité') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Sélectionnez les permis de conduire que vous possédez.") }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.permits.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($allPermits as $permit)
                <label class="flex items-center p-3 border border-gray-200 rounded-lg bg-white shadow-sm cursor-pointer hover:bg-gray-50 transition-colors">
                    <input 
                        type="checkbox" 
                        name="permits[]" 
                        value="{{ $permit->id }}" 
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        {{ $user->permits->contains($permit->id) ? 'checked' : '' }}
                    >
                    <span class="ml-2 text-sm font-medium text-gray-700">{{ $permit->label }}</span>
                </label>
            @endforeach
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Enregistrer les permis') }}</x-primary-button>

            @if (session('status') === 'permits-updated')
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
