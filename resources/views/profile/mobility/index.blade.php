<x-app-layout>
    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Header -->
            <div class="mb-12">
                <h1 class="text-4xl font-black text-slate-900 mb-2">Ma Zone de Confort</h1>
                <p class="text-lg text-slate-500 font-medium mb-8">Définissez votre point de départ et votre périmètre de recherche.</p>

                <!-- Info Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-blue-600 rounded-3xl p-6 text-white shadow-xl shadow-blue-100 flex flex-col justify-between overflow-hidden relative">
                        <div class="relative z-10">
                            <h3 class="text-lg font-black mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                Rayon Pivot
                            </h3>
                            <p class="text-blue-100 text-[11px] leading-relaxed font-medium">
                                Notre algorithme ne vous exclut jamais brutalement. Plus vous êtes proche de votre centre, plus votre score de matching est élevé.
                            </p>
                        </div>
                        <div class="absolute -bottom-4 -right-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
                    </div>

                    <div class="md:col-span-2 bg-white rounded-3xl p-6 border border-slate-100 shadow-sm grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-slate-900 font-bold text-xs uppercase tracking-wider">
                                <div class="w-6 h-6 rounded-lg bg-emerald-50 flex items-center justify-center text-[10px] text-emerald-500 font-black">1</div>
                                Cœur de Cible
                            </div>
                            <p class="text-[11px] text-slate-400 leading-tight">
                                <strong class="text-emerald-600 font-black">MAXIMUM (30 pts)</strong> : Les offres situées dans votre ville ou très proches reçoivent le score maximal.
                            </p>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-slate-900 font-bold text-xs uppercase tracking-wider">
                                <div class="w-6 h-6 rounded-lg bg-blue-50 flex items-center justify-center text-[10px] text-blue-500 font-black">2</div>
                                Zone Étendue
                            </div>
                            <p class="text-[11px] text-slate-400 leading-tight">
                                <strong class="text-blue-600 font-black">DÉGRESSIF</strong> : Au-delà du rayon, le score diminue doucement pour laisser place aux opportunités exceptionnelles.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div x-data="mobilityApp({
                zip_code: {{ Js::from($user->zip_code) }},
                radius: {{ Js::from($user->radius ?? 20) }},
                permits: {{ Js::from($userPermitIds) }},
                nonePermitId: {{ Js::from(\App\Models\Permit::where('code', 'NONE')->first()?->id ?? 0) }},
                csrfToken: {{ Js::from(csrf_token()) }},
                routes: {
                    update: {{ Js::from(route('profile.mobility.update')) }}
                }
            })">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <!-- Left Column: Form -->
                    <div class="lg:col-span-5 space-y-6">
                        <div class="bg-white rounded-[2.5rem] p-8 shadow-xl border border-slate-100">
                            <div class="space-y-10">
                                <!-- Postal Code -->
                                <div>
                                    <label for="zip_code" class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">
                                        📍 Mon Point de Départ
                                    </label>
                                    <div class="relative group">
                                        <input 
                                            type="text" 
                                            id="zip_code"
                                            x-model="zip_code"
                                            @input.debounce.500ms="save()"
                                            class="block w-full px-6 py-5 bg-slate-50 border-2 border-slate-50 rounded-2xl text-2xl font-black text-slate-800 placeholder-slate-300 focus:bg-white focus:border-blue-500 focus:ring-0 transition-all outline-none"
                                            placeholder="ex: 5000"
                                        >
                                    </div>
                                    <p class="mt-3 text-[10px] text-slate-400 font-bold italic uppercase tracking-tighter">
                                        Code postal de votre domicile ou lieu de départ.
                                    </p>
                                </div>

                                <!-- Distance Slider -->
                                <div>
                                    <div class="flex justify-between items-end mb-6">
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                            🚗 Rayon de Mobilité
                                        </label>
                                        <div class="flex items-baseline gap-1">
                                            <span class="text-4xl font-black text-blue-600 tracking-tighter" x-text="radius"></span>
                                            <span class="text-sm font-black text-blue-400">km</span>
                                        </div>
                                    </div>
                                    
                                    <div class="relative py-2">
                                        <input 
                                            type="range" 
                                            min="0" 
                                            max="200" 
                                            step="5" 
                                            x-model="radius"
                                            @change="save()"
                                            class="w-full h-3 bg-slate-100 rounded-full appearance-none cursor-pointer accent-blue-600"
                                        >
                                        <div class="flex justify-between text-[9px] text-slate-300 font-black mt-4 px-1 uppercase tracking-widest">
                                            <span>Local</span>
                                            <span>100km</span>
                                            <span>200km</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Driving Licenses -->
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">
                                        🪪 Permis de Conduire
                                    </label>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($allPermits as $permit)
                                            <button 
                                                type="button"
                                                @click="togglePermit({{ $permit->id }})"
                                                :class="{
                                                    'bg-blue-600 text-white shadow-lg shadow-blue-100 border-blue-600': permits.includes({{ $permit->id }}),
                                                    'bg-white text-slate-600 border-slate-100 hover:border-blue-200': !permits.includes({{ $permit->id }})
                                                }"
                                                class="px-4 py-2.5 rounded-xl border-2 text-xs font-black transition-all flex items-center gap-2"
                                            >
                                                <span class="opacity-50">#</span>
                                                {{ $permit->label }}
                                            </button>
                                        @endforeach
                                    </div>
                                    <p class="mt-3 text-[10px] text-slate-400 font-bold italic uppercase tracking-tighter">
                                        Certaines offres sont inaccessibles sans permis spécifique.
                                    </p>
                                </div>
                            </div>

                            <!-- Save Feedback -->
                            <div class="mt-8 h-8 flex items-center justify-center">
                                <template x-if="isSaving">
                                    <div class="flex items-center gap-2 text-blue-500 animate-pulse">
                                        <div class="w-1.5 h-1.5 bg-blue-500 rounded-full"></div>
                                        <span class="text-[10px] font-black uppercase tracking-widest">Synchronisation...</span>
                                    </div>
                                </template>
                                <template x-if="showSuccess">
                                    <div class="flex items-center gap-2 text-emerald-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        <span class="text-[10px] font-black uppercase tracking-widest">Préférences à jour</span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Secondary Info -->
                        <div class="bg-indigo-50 rounded-3xl p-6 border border-indigo-100">
                            <h4 class="text-xs font-black text-indigo-900 uppercase tracking-widest mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Pourquoi ces réglages ?
                            </h4>
                            <p class="text-[11px] text-indigo-700/70 leading-relaxed font-medium">
                                Le lieu de vie est le point de départ de toutes nos analyses. En combinant votre code postal avec votre rayon, l'IA priorise les offres qui respectent votre équilibre vie pro / vie perso.
                            </p>
                        </div>
                    </div>

                    <!-- Right Column: Visualization & Guide -->
                    <div class="lg:col-span-7 space-y-6">
                        <!-- Visual Explanation Card -->
                        <div class="bg-white rounded-[2.5rem] p-8 md:p-12 shadow-xl border border-slate-100 relative overflow-hidden h-full flex flex-col">
                            <div class="relative z-10 flex-grow">
                                <h3 class="text-2xl font-black text-slate-900 mb-8 flex items-center gap-3">
                                    <span class="text-3xl">🧭</span>
                                    Visualisation du Matching
                                </h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                                    <div class="space-y-8">
                                        <div class="relative w-full aspect-square bg-slate-50 rounded-full border-4 border-dashed border-slate-100 flex items-center justify-center overflow-hidden">
                                            <!-- Center Dot -->
                                            <div class="w-4 h-4 bg-blue-600 rounded-full shadow-lg shadow-blue-200 z-30"></div>
                                            <!-- Pulsing Radius -->
                                            <div 
                                                class="absolute bg-blue-500/10 border-2 border-blue-500/30 rounded-full transition-all duration-500 ease-out"
                                                :style="`width: ${Math.max(10, radius/2)}%; height: ${Math.max(10, radius/2)}%`"
                                            ></div>
                                            <!-- Labels -->
                                            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 text-[9px] font-black text-slate-300 uppercase tracking-widest" x-text="zip_code || 'Départ'"></div>
                                        </div>
                                    </div>

                                    <div class="space-y-6">
                                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Impact sur le score final</h4>
                                        
                                        <div class="space-y-5">
                                            <div class="space-y-2">
                                                <div class="flex justify-between text-[10px] font-black uppercase tracking-tighter">
                                                    <span class="text-slate-500">Cœur (0km)</span>
                                                    <span class="text-emerald-500">+30 pts</span>
                                                </div>
                                                <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                    <div class="h-full bg-emerald-500 w-full"></div>
                                                </div>
                                            </div>

                                            <div class="space-y-2">
                                                <div class="flex justify-between text-[10px] font-black uppercase tracking-tighter">
                                                    <span class="text-slate-500" x-text="'Limite (' + radius + 'km)'"></span>
                                                    <span class="text-blue-500">+20 pts</span>
                                                </div>
                                                <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                    <div class="h-full bg-blue-500 w-2/3"></div>
                                                </div>
                                            </div>

                                            <div class="space-y-2">
                                                <div class="flex justify-between text-[10px] font-black uppercase tracking-tighter">
                                                    <span class="text-slate-500" x-text="'Distance x2 (' + (radius*2) + 'km)'"></span>
                                                    <span class="text-slate-400">+15 pts</span>
                                                </div>
                                                <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                    <div class="h-full bg-slate-300 w-1/2"></div>
                                                </div>
                                            </div>

                                            <div class="space-y-2">
                                                <div class="flex justify-between text-[10px] font-black uppercase tracking-tighter">
                                                    <span class="text-slate-400" x-text="'Très Éloigné (' + (radius*5) + 'km)'"></span>
                                                    <span class="text-slate-300" x-text="Math.round(30 * (radius / (radius + ((radius*5) / 2)))) + ' pts'"></span>
                                                </div>
                                                <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                    <div class="h-full bg-slate-200" :style="`width: ${(radius / (radius + ((radius*5) / 2))) * 100}%`"></div >
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-8 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                            <p class="text-[10px] text-slate-500 leading-relaxed font-medium italic">
                                                "Notre système préfère toujours vous proposer un job un peu plus loin s'il est parfaitement aligné avec vos compétences, plutôt que de l'ignorer totalement."
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom Actions -->
                            <div class="mt-12 pt-8 border-t border-slate-50 flex items-center justify-between">
                                <a href="{{ route('discovery.index') }}" class="text-xs font-black text-slate-400 uppercase tracking-widest hover:text-slate-600 transition-colors">
                                    ← Métiers ROME
                                </a>
                                <a href="{{ route('dashboard') }}" class="px-8 py-4 bg-slate-900 text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-black transition-all hover:scale-105 active:scale-95 flex items-center gap-2">
                                    Explorer mes matchs
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
