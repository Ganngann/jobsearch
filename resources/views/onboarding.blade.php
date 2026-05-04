<x-guest-layout>
    <div class="max-w-xl mx-auto py-8">
        <!-- Header -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-indigo-600/20 rounded-3xl mb-6 border border-indigo-500/20">
                <svg class="w-10 h-10 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1 class="text-3xl font-black text-white mb-4">Salut {{ Auth::user()->name }} !</h1>
            <p class="text-slate-400 leading-relaxed text-lg">
                Ravi de t'avoir parmi nous. Pour que je puisse scanner les <span class="text-white font-bold">{{ \App\Models\JobOffer::where('status', 'active')->count() }}</span> offres du Forem et te trouver celles qui te correspondent vraiment, j'ai besoin de te connaître un peu.
            </p>
        </div>

        <!-- Choices -->
        <div class="space-y-4">
            <!-- Choice 1: AI Builder -->
            <a href="{{ route('profile.builder') }}" class="group block p-6 bg-white/5 border border-white/10 rounded-[2rem] hover:border-indigo-500/50 hover:bg-indigo-500/5 transition-all duration-300">
                <div class="flex items-center gap-6">
                    <div class="flex-shrink-0 w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </div>
                    <div class="flex-grow">
                        <h3 class="text-xl font-bold text-white mb-1">Discuter avec mon coach IA</h3>
                        <p class="text-slate-400 text-sm">Le moyen le plus sympa. On discute 2 minutes et l'IA s'occupe de tout.</p>
                    </div>
                    <div class="text-slate-600 group-hover:text-indigo-400 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            </a>

            <!-- Choice 2: CV Import -->
            <a href="{{ route('profile.edit') }}" class="group block p-6 bg-white/5 border border-white/10 rounded-[2rem] hover:border-violet-500/50 hover:bg-violet-500/5 transition-all duration-300">
                <div class="flex items-center gap-6">
                    <div class="flex-shrink-0 w-16 h-16 bg-violet-600/20 border border-violet-500/20 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                    </div>
                    <div class="flex-grow">
                        <h3 class="text-xl font-bold text-white mb-1">Importer mon CV</h3>
                        <p class="text-slate-400 text-sm">Si tu as déjà un CV prêt (PDF ou Word), l'IA va l'analyser en quelques secondes.</p>
                    </div>
                    <div class="text-slate-600 group-hover:text-violet-400 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            </a>
        </div>

        <!-- Footer Help -->
        <div class="mt-12 text-center">
            <p class="text-slate-500 text-sm italic">
                "Promis, ça prend moins de temps que de lire 3 annonces au hasard."
            </p>
        </div>
    </div>
</x-guest-layout>
