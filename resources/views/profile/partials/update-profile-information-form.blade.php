<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Dimension Humaine & Informations Générales') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Mettez à jour votre récit de vie, vos aspirations et vos informations de contact.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <x-input-label for="name" :value="__('Nom')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="headline" :value="__('Titre Professionnel')" />
                <x-text-input id="headline" name="headline" type="text" class="mt-1 block w-full" :value="old('headline', $user->headline)" placeholder="ex: Développeur Fullstack, Expert SRE..." />
                <x-input-error class="mt-2" :messages="$errors->get('headline')" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div>
                        <p class="text-sm mt-2 text-gray-800">
                            {{ __('Your email address is unverified.') }}

                            <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 font-medium text-sm text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <div>
                <x-input-label for="location" :value="__('Localisation')" />
                <x-text-input id="location" name="location" type="text" class="mt-1 block w-full" :value="old('location', $user->location)" placeholder="ex: Namur, Belgique" />
                <x-input-error class="mt-2" :messages="$errors->get('location')" />
            </div>
        </div>

        <div>
            <x-input-label for="profile_text" :value="__('Récit de Vie / Dimension Humaine')" />
            <p class="text-xs text-gray-500 mb-2 italic">Racontez votre parcours, vos défis relevés et ce qui fait de vous un candidat unique au-delà de la technique.</p>
            <x-textarea id="profile_text" name="profile_text" class="mt-1 block w-full" rows="6">{{ old('profile_text', $user->profile_text) }}</x-textarea>
            <x-input-error class="mt-2" :messages="$errors->get('profile_text')" />
        </div>

        <div>
            <x-input-label for="aspirations" :value="__('Valeurs & Aspirations')" />
            <p class="text-xs text-gray-500 mb-2 italic">Qu'est-ce qui est primordial pour vous dans votre futur job ? (ex: Open Source, Impact environnemental, Autonomie...)</p>
            <x-textarea id="aspirations" name="aspirations" class="mt-1 block w-full" rows="3">{{ old('aspirations', $user->aspirations) }}</x-textarea>
            <x-input-error class="mt-2" :messages="$errors->get('aspirations')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Enregistrer les modifications') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
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

    @include('profile.partials.manage-facts-list')
</section>
