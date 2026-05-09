<x-app-layout>
    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Header -->
            <div class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h1 class="text-4xl font-black text-slate-900 mb-2">Ma Zone de Confort</h1>
                    <p class="text-lg text-slate-500 font-medium">Définissez vos critères administratifs et votre périmètre de recherche.</p>
                </div>

                <x-profile-publish-button label="Mettre à jour mes opportunités" size="lg" />
            </div>

            <!-- Main Content Grid -->
            <div x-data="mobilityApp({
                zip_code: {{ Js::from($user->zip_code) }},
                radius: {{ Js::from($user->radius ?? 20) }},
                permits: {{ Js::from($userPermitIds) }},
                contract_preferences: {{ Js::from($userContractPreferences) }},
                nonePermitId: {{ Js::from(\App\Models\Permit::where('code', 'NONE')->first()?->id ?? 0) }},
                csrfToken: {{ Js::from(csrf_token()) }},
                routes: {
                    update: {{ Js::from(route('profile.mobility.update')) }}
                }
            })">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    
                    <!-- LEFT COLUMN: Permits & Contracts -->
                    <div class="lg:col-span-5 space-y-6">
                        <div class="bg-white rounded-[2.5rem] p-8 shadow-xl border border-slate-100">
                            <div class="space-y-10">
                                <!-- Contract Preferences -->
                                <div>
                                    <div class="flex items-center justify-between mb-4">
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                            💼 Mes Préférences de Contrat
                                        </label>
                                        <template x-if="contract_preferences.length === 0">
                                            <span class="text-[9px] font-black text-indigo-500 bg-indigo-50 px-2 py-0.5 rounded-full uppercase tracking-tighter animate-pulse">
                                                Tous acceptés par défaut
                                            </span>
                                        </template>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        @foreach($allContractTypes as $type)
                                            <button 
                                                type="button"
                                                @click="toggleContract('{{ $type }}')"
                                                :class="{
                                                    'bg-indigo-600 text-white shadow-lg shadow-indigo-100 border-indigo-600 ring-2 ring-indigo-600 ring-offset-2': contract_preferences.includes('{{ $type }}'),
                                                    'bg-indigo-50 border-indigo-200 text-indigo-700': contract_preferences.length === 0,
                                                    'bg-white text-slate-600 border-slate-100 hover:border-indigo-200': contract_preferences.length > 0 && !contract_preferences.includes('{{ $type }}')
                                                }"
                                                class="px-4 py-3 rounded-xl border-2 text-[11px] font-black transition-all flex items-center justify-between text-left group"
                                            >
                                                <div class="flex items-center gap-3">
                                                    <span class="opacity-50 shrink-0 group-hover:scale-110 transition-transform">#</span>
                                                    <span>{{ $type }}</span>
                                                </div>
                                                
                                                <!-- Checkmark Indicator -->
                                                <div 
                                                    class="w-5 h-5 rounded-full flex items-center justify-center transition-all"
                                                    :class="{
                                                        'bg-white text-indigo-600': contract_preferences.includes('{{ $type }}'),
                                                        'bg-slate-100 text-transparent': !contract_preferences.includes('{{ $type }}') && contract_preferences.length > 0,
                                                        'bg-indigo-200 text-indigo-600': contract_preferences.length === 0
                                                    }"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Driving Licenses -->
                                <div>
                                    <div class="mb-4">
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                            🪪 Mes Permis de Conduire
                                        </label>
                                        <p class="text-[9px] text-slate-400 font-medium mt-1">Sélectionnez les permis que vous possédez actuellement.</p>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        @foreach($allPermits as $permit)
                                            <button 
                                                type="button"
                                                @click="togglePermit({{ $permit->id }})"
                                                :class="{
                                                    'bg-indigo-600 text-white shadow-lg shadow-indigo-100 border-indigo-600 ring-2 ring-indigo-600 ring-offset-2': permits.includes({{ $permit->id }}),
                                                    'bg-white text-slate-600 border-slate-100 hover:border-indigo-200': !permits.includes({{ $permit->id }})
                                                }"
                                                class="px-4 py-3 rounded-xl border-2 text-[11px] font-black transition-all flex items-center justify-between text-left group"
                                            >
                                                <div class="flex items-center gap-3">
                                                    <span class="opacity-50 shrink-0 group-hover:scale-110 transition-transform">#</span>
                                                    <span>{{ $permit->label }}</span>
                                                </div>

                                                <!-- Checkmark Indicator -->
                                                <div 
                                                    class="w-5 h-5 rounded-full flex items-center justify-center transition-all"
                                                    :class="{
                                                        'bg-white text-indigo-600': permits.includes({{ $permit->id }}),
                                                        'bg-slate-100 text-transparent': !permits.includes({{ $permit->id }})
                                                    }"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <!-- Save Feedback -->
                            <div class="mt-8 h-8 flex items-center justify-center">
                                <template x-if="isSaving">
                                    <div class="flex items-center gap-2 text-indigo-500 animate-pulse">
                                        <div class="w-1.5 h-1.5 bg-indigo-500 rounded-full"></div>
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
                    </div>

                    <!-- RIGHT COLUMN: Locality, Range & Visualization -->
                    <div class="lg:col-span-7 space-y-6">
                        <!-- Localisation & Slider -->
                        <div class="bg-white rounded-[2.5rem] p-8 shadow-xl border border-slate-100">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
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
                                            class="block w-full px-6 py-5 bg-slate-50 border-2 border-slate-50 rounded-2xl text-2xl font-black text-slate-800 placeholder-slate-300 focus:bg-white focus:border-indigo-500 focus:ring-0 transition-all outline-none"
                                            placeholder="ex: 5000"
                                        >
                                    </div>
                                </div>

                                <!-- Distance Slider -->
                                <div>
                                    <div class="flex justify-between items-end mb-6">
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                            🚗 Rayon de Mobilité
                                        </label>
                                        <div class="flex items-baseline gap-1">
                                            <span class="text-4xl font-black text-indigo-600 tracking-tighter" x-text="radius"></span>
                                            <span class="text-sm font-black text-indigo-400">km</span>
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
                                            class="w-full h-3 bg-slate-100 rounded-full appearance-none cursor-pointer accent-indigo-600"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Visualization Card -->
                        <div class="bg-white rounded-[2.5rem] p-8 md:p-12 shadow-xl border border-slate-100 relative overflow-hidden flex flex-col">
                            <div class="relative z-10">
                                <h3 class="text-2xl font-black text-slate-900 mb-8 flex items-center gap-3">
                                    <span class="text-3xl">🧭</span>
                                    Visualisation du Matching
                                </h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                                    <div class="space-y-8">
                                        <div class="relative w-full aspect-square bg-slate-50 rounded-full border-4 border-dashed border-slate-100 flex items-center justify-center overflow-hidden">
                                            <!-- Center Dot -->
                                            <div class="w-4 h-4 bg-indigo-600 rounded-full shadow-lg shadow-indigo-200 z-30"></div>
                                            <!-- Pulsing Radius -->
                                            <div 
                                                class="absolute bg-indigo-500/10 border-2 border-indigo-500/30 rounded-full transition-all duration-500 ease-out"
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
                                                    <span class="text-indigo-500">+20 pts</span>
                                                </div>
                                                <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                    <div class="h-full bg-indigo-500 w-2/3"></div>
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
                                                "Notre système préfère toujours vous proposer un job un peu plus loin s'il est parfaitement aligné avec vos compétences."
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
