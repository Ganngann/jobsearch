<x-app-layout>
    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Header Section -->
            <div class="mb-12 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-100 text-blue-600 rounded-3xl mb-6 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight mb-4">Ma Zone de Mobilité</h1>
                <p class="text-lg text-slate-500 max-w-xl mx-auto leading-relaxed">
                    Définissez votre point de départ et la distance maximale que vous êtes prêt à parcourir pour trouver le job idéal près de chez vous.
                </p>
            </div>

            <!-- Main Card -->
            <div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
                <div class="p-8 md:p-12">
                    <div x-data="{
                        zip_code: '{{ $user->zip_code }}',
                        radius: {{ $user->radius ?? 20 }},
                        isSaving: false,
                        showSuccess: false,

                        async save() {
                            this.isSaving = true;
                            try {
                                const response = await fetch('{{ route('profile.mobility.update') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        _method: 'PATCH',
                                        zip_code: this.zip_code,
                                        radius: this.radius
                                    })
                                });
                                if (!response.ok) throw new Error('Erreur');
                                
                                window.dispatchEvent(new CustomEvent('mobility-updated'));
                                this.showSuccess = true;
                                setTimeout(() => { this.showSuccess = false; }, 3000);
                            } catch (e) {
                                console.error(e);
                            } finally {
                                setTimeout(() => { this.isSaving = false; }, 600);
                            }
                        }
                    }">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
                            <!-- Left: Form Controls -->
                            <div class="space-y-10">
                                <!-- Postal Code -->
                                <div>
                                    <label for="zip_code" class="block text-sm font-black text-slate-400 uppercase tracking-widest mb-4">
                                        🏠 Code Postal de départ
                                    </label>
                                    <div class="relative group">
                                        <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                                            <span class="text-slate-300 font-bold group-focus-within:text-blue-500 transition-colors text-xl">📍</span>
                                        </div>
                                        <input 
                                            type="text" 
                                            id="zip_code"
                                            x-model="zip_code"
                                            @input.debounce.500ms="save()"
                                            class="block w-full pl-14 pr-6 py-6 bg-slate-50 border-2 border-slate-50 rounded-2xl text-2xl font-black text-slate-800 placeholder-slate-300 focus:bg-white focus:border-blue-500 focus:ring-0 transition-all outline-none"
                                            placeholder="ex: 5000"
                                        >
                                    </div>
                                    <p class="mt-3 text-xs text-slate-400 font-medium italic">
                                        Le matching calculera la distance réelle par rapport à ce point.
                                    </p>
                                </div>

                                <!-- Distance Slider -->
                                <div>
                                    <div class="flex justify-between items-end mb-6">
                                        <label class="block text-sm font-black text-slate-400 uppercase tracking-widest">
                                            🚗 Rayon de recherche
                                        </label>
                                        <div class="flex items-baseline gap-1">
                                            <span class="text-5xl font-black text-blue-600 tracking-tighter" x-text="radius"></span>
                                            <span class="text-xl font-black text-blue-400">km</span>
                                        </div>
                                    </div>
                                    
                                    <div class="relative py-4">
                                        <input 
                                            type="range" 
                                            min="0" 
                                            max="200" 
                                            step="5" 
                                            x-model="radius"
                                            @change="save()"
                                            class="w-full h-4 bg-slate-100 rounded-full appearance-none cursor-pointer accent-blue-600"
                                        >
                                        <div class="flex justify-between text-[11px] text-slate-300 font-black mt-6 px-1 uppercase tracking-tighter">
                                            <span>Local (0km)</span>
                                            <span>50km</span>
                                            <span>100km</span>
                                            <span>150km</span>
                                            <span>200km+</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Visual Feedback / Illustration -->
                            <div class="bg-slate-50 rounded-[2rem] p-8 flex flex-col items-center justify-center relative min-h-[350px] border border-slate-100/50">
                                <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-[0.03]">
                                    <div class="w-64 h-64 border-8 border-blue-600 rounded-full scale-[2] animate-pulse"></div>
                                    <div class="w-64 h-64 border-8 border-blue-600 rounded-full scale-[3] absolute"></div>
                                </div>
                                
                                <div class="relative z-10 text-center">
                                    <div class="text-7xl mb-6">🎯</div>
                                    <h3 class="text-xl font-black text-slate-800 mb-2">Ma Zone de Confort</h3>
                                    <p class="text-sm text-slate-500 max-w-[200px] mx-auto font-medium">
                                        Vous ciblez les opportunités dans un cercle de <span class="text-blue-600 font-bold" x-text="radius + ' km'"></span> autour de <span class="text-blue-600 font-bold" x-text="zip_code || 'votre ville'"></span>.
                                    </p>
                                </div>

                                <!-- Status Toast -->
                                <div 
                                    x-show="showSuccess" 
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 translate-y-4"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    class="absolute bottom-6 left-1/2 -translate-x-1/2 bg-emerald-500 text-white text-xs font-black px-6 py-3 rounded-full shadow-lg flex items-center gap-2"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    PRÉFÉRENCES ENREGISTRÉES
                                </div>
                            </div>
                        </div>

                        </div>

                        <!-- Info Section: How matching works -->
                        <div class="mt-16 bg-blue-600 rounded-[2rem] p-8 md:p-12 text-white relative overflow-hidden shadow-2xl shadow-blue-200">
                            <!-- Decor -->
                            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32 blur-3xl"></div>
                            
                            <div class="relative z-10">
                                <div class="flex items-center gap-4 mb-6">
                                    <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center text-2xl">💡</div>
                                    <h3 class="text-2xl font-black tracking-tight">Comment fonctionne le calcul ?</h3>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                                    <div class="space-y-4">
                                        <p class="text-blue-50 leading-relaxed font-medium">
                                            Votre rayon n'est pas un mur, mais un <strong class="text-white">curseur de priorité</strong>. Nous utilisons une formule de "Rayon Pivot" pour ne jamais rater l'offre idéale.
                                        </p>
                                        <ul class="space-y-3">
                                            <li class="flex items-start gap-3">
                                                <span class="mt-1 w-5 h-5 bg-white/20 rounded-full flex items-center justify-center text-[10px] font-black shrink-0">1</span>
                                                <span><strong class="text-white">Proximité Totale :</strong> À 0km, vous recevez le bonus maximum de <strong class="text-white">30 points</strong>.</span>
                                            </li>
                                            <li class="flex items-start gap-3">
                                                <span class="mt-1 w-5 h-5 bg-white/20 rounded-full flex items-center justify-center text-[10px] font-black shrink-0">2</span>
                                                <span><strong class="text-white">Zone de Confort :</strong> À la limite de votre rayon (ex: <span x-text="radius"></span>km), vous gardez encore <strong class="text-white">20 points</strong>.</span>
                                            </li>
                                            <li class="flex items-start gap-3">
                                                <span class="mt-1 w-5 h-5 bg-white/20 rounded-full flex items-center justify-center text-[10px] font-black shrink-0">3</span>
                                                <span><strong class="text-white">Ouverture :</strong> Au-delà du rayon, le score diminue doucement sans jamais exclure brutalement une offre exceptionnelle.</span>
                                            </li>
                                        </ul>
                                    </div>
                                    
                                    <div class="bg-white/10 rounded-3xl p-6 border border-white/10 backdrop-blur-md">
                                        <h4 class="text-sm font-black uppercase tracking-widest mb-6 opacity-80 text-center">Estimation des points</h4>
                                        <div class="space-y-6">
                                            <!-- Chart Item 1 -->
                                            <div class="space-y-2">
                                                <div class="flex justify-between text-xs font-black">
                                                    <span>TRÈS PROCHE (0km)</span>
                                                    <span class="text-emerald-300">30 PTS</span>
                                                </div>
                                                <div class="h-2 bg-white/10 rounded-full overflow-hidden">
                                                    <div class="h-full bg-emerald-400 w-full"></div>
                                                </div>
                                            </div>
                                            <!-- Chart Item 2 -->
                                            <div class="space-y-2">
                                                <div class="flex justify-between text-xs font-black">
                                                    <span x-text="'LIMITE (' + radius + 'km)'"></span>
                                                    <span class="text-blue-200">20 PTS</span>
                                                </div>
                                                <div class="h-2 bg-white/10 rounded-full overflow-hidden">
                                                    <div class="h-full bg-blue-300 w-[66%]"></div>
                                                </div>
                                            </div>
                                            <!-- Chart Item 3 -->
                                            <div class="space-y-2">
                                                <div class="flex justify-between text-xs font-black">
                                                    <span x-text="'HORS ZONE (' + (radius * 2) + 'km)'"></span>
                                                    <span class="text-blue-100/50">15 PTS</span>
                                                </div>
                                                <div class="h-2 bg-white/10 rounded-full overflow-hidden">
                                                    <div class="h-full bg-white/30 w-[50%]"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="mt-12 pt-8 border-t border-slate-50 flex items-center justify-between">
                            <a href="{{ route('discovery.index') }}" class="text-slate-400 hover:text-slate-600 font-bold text-sm transition-colors flex items-center gap-2">
                                ← Retour aux métiers
                            </a>
                            
                            <a 
                                href="{{ route('dashboard') }}" 
                                class="bg-slate-900 text-white px-10 py-5 rounded-2xl font-black text-lg transition-all hover:bg-black hover:scale-105 hover:shadow-2xl shadow-slate-900/20 flex items-center gap-3"
                                :class="!zip_code && 'opacity-50 pointer-events-none'"
                            >
                                Explorer mes matchs
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
