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

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" 
          x-data="{ 
            isMagicFilling: false,
            headline: '{{ addslashes($user->headline) }}',
            profile_text: `{{ addslashes($user->profile_text) }}`,
            aspirations: `{{ addslashes($user->aspirations) }}`,

            async magicFill() {
                this.isMagicFilling = true;
                try {
                    const response = await fetch('{{ route('profile.magic-fill') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (data.error) {
                        window.dispatchEvent(new CustomEvent('notify', { detail: { message: data.error, type: 'error' } }));
                    } else {
                        this.headline = data.headline;
                        this.profile_text = data.profile_text;
                        this.aspirations = data.aspirations;
                        window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Profil mis à jour par l\'IA !' } }));
                    }
                } catch (e) {
                    console.error(e);
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Erreur lors de la génération', type: 'error' } }));
                } finally {
                    this.isMagicFilling = false;
                }
            }
          }">
        @csrf
        @method('patch')

        {{-- Barre d'outils IA --}}
        <div class="flex justify-end mb-4">
            <button type="button" @click="magicFill()" :disabled="isMagicFilling"
                    class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-indigo-600 to-violet-600 border border-transparent rounded-xl font-bold text-[11px] text-white uppercase tracking-widest hover:from-indigo-700 hover:to-violet-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all disabled:opacity-50">
                <svg x-show="!isMagicFilling" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                <svg x-show="isMagicFilling" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="isMagicFilling ? 'Génération en cours...' : 'Auto-compléter via l\'IA'"></span>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <x-input-label for="name" :value="__('Nom')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="headline" :value="__('Titre Professionnel')" />
                <x-text-input id="headline" name="headline" type="text" class="mt-1 block w-full" x-model="headline" placeholder="ex: Développeur Fullstack, Expert SRE..." />
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
            <x-textarea id="profile_text" name="profile_text" class="mt-1 block w-full" rows="6" x-model="profile_text"></x-textarea>
            <x-input-error class="mt-2" :messages="$errors->get('profile_text')" />
        </div>

        <div>
            <x-input-label for="aspirations" :value="__('Valeurs & Aspirations')" />
            <p class="text-xs text-gray-500 mb-2 italic">Qu'est-ce qui est primordial pour vous dans votre futur job ? (ex: Open Source, Impact environnemental, Autonomie...)</p>
            <x-textarea id="aspirations" name="aspirations" class="mt-1 block w-full" rows="3" x-model="aspirations"></x-textarea>
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
    </form>
</section>
