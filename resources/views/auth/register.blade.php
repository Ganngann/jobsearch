<x-guest-layout>
    <div class="mb-8 text-center">
        <h2 class="text-2xl font-black text-white mb-2">Rejoins l'aventure</h2>
        <p class="text-slate-400 text-sm">Crée ton accès pour commencer à matcher avec les meilleures offres du Forem.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Nom ou Pseudo')" class="text-slate-300 mb-1.5" />
            <x-text-input id="name" class="block w-full bg-white/5 border-white/10 text-white focus:ring-indigo-500 focus:border-indigo-500 rounded-xl" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Comment je t'appelle ?" />
            <x-input-error :messages="$errors->get('name')" class="mt-2 text-rose-400" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" class="text-slate-300 mb-1.5" />
            <x-text-input id="email" class="block w-full bg-white/5 border-white/10 text-white focus:ring-indigo-500 focus:border-indigo-500 rounded-xl" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="ton@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-rose-400" />
        </div>

        <!-- Password -->
        <div x-data="{ show: false }">
            <x-input-label for="password" :value="__('Mot de passe')" class="text-slate-300 mb-1.5" />
            <div class="relative">
                <x-text-input id="password" class="block w-full bg-white/5 border-white/10 text-white focus:ring-indigo-500 focus:border-indigo-500 rounded-xl pr-10"
                                type="password"
                                x-bind:type="show ? 'text' : 'password'"
                                name="password"
                                required autocomplete="new-password" 
                                placeholder="••••••••" />
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white transition-colors focus:outline-none" aria-label="Toggle password visibility">
                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg x-show="show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-rose-400" />
        </div>

        <!-- Confirm Password -->
        <div x-data="{ show: false }">
            <x-input-label for="password_confirmation" :value="__('Confirme le mot de passe')" class="text-slate-300 mb-1.5" />
            <div class="relative">
                <x-text-input id="password_confirmation" class="block w-full bg-white/5 border-white/10 text-white focus:ring-indigo-500 focus:border-indigo-500 rounded-xl pr-10"
                                type="password"
                                x-bind:type="show ? 'text' : 'password'"
                                name="password_confirmation" required autocomplete="new-password"
                                placeholder="••••••••" />
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white transition-colors focus:outline-none" aria-label="Toggle password visibility">
                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg x-show="show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-rose-400" />
        </div>

        <div class="pt-2">
            <button class="w-full py-4 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-500 transition-all shadow-xl shadow-indigo-500/20">
                Créer mon accès
            </button>
        </div>

        <div class="text-center pt-4">
            <a class="text-sm text-slate-500 hover:text-white transition-colors" href="{{ route('login') }}">
                Déjà inscrit ? <span class="text-indigo-400 font-bold">Connecte-toi</span>
            </a>
        </div>
    </form>
</x-guest-layout>
