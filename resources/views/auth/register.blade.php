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
        <div>
            <x-input-label for="password" :value="__('Mot de passe')" class="text-slate-300 mb-1.5" />
            <x-text-input id="password" class="block w-full bg-white/5 border-white/10 text-white focus:ring-indigo-500 focus:border-indigo-500 rounded-xl"
                            type="password"
                            name="password"
                            required autocomplete="new-password" 
                            placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-rose-400" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirme le mot de passe')" class="text-slate-300 mb-1.5" />
            <x-text-input id="password_confirmation" class="block w-full bg-white/5 border-white/10 text-white focus:ring-indigo-500 focus:border-indigo-500 rounded-xl"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password"
                            placeholder="••••••••" />
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
