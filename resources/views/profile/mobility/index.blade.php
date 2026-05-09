<x-app-layout>
    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Header -->
            <div class="mb-12">
                <h1 class="text-4xl font-black text-slate-900 mb-2">Ma Zone de Confort</h1>
                <p class="text-lg text-slate-500 font-medium mb-8">Définissez vos critères administratifs et votre périmètre de recherche.</p>
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
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">
                                        💼 Types de Contrat Souhaités
                                    </label>
                                    <div class="flex flex-col gap-2">
                                        @foreach($allContractTypes as $type)
                                            <button 
                                                type="button"
                                                @click="toggleContract('{{ $type }}')"
                                                :class="{
                                                    'bg-indigo-600 text-white shadow-lg shadow-indigo-100 border-indigo-600': contract_preferences.includes('{{ $type }}'),
                                                    'bg-white text-slate-600 border-slate-100 hover:border-indigo-200': !contract_preferences.includes('{{ $type }}')
                                                }"
                                                class="px-4 py-3 rounded-xl border-2 text-[11px] font-black transition-all flex items-center gap-3 text-left"
                                            >
                                                <span class="opacity-50 shrink-0">#</span>
                                                <span>{{ $type }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                    <p class="mt-3 text-[10px] text-slate-400 font-bold italic uppercase tracking-tighter">
                                        Laissez vide pour tout accepter.
                                    </p>
                                </div>

                                <!-- Driving Licenses -->
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">
                                        🪪 Permis de Conduire
                                    </label>
                                    <div class="flex flex-col gap-2">
                                        @foreach($allPermits as $permit)
                                            <button 
                                                type="button"
                                                @click="togglePermit({{ $permit->id }})"
                                                :class="{
                                                    'bg-blue-600 text-white shadow-lg shadow-blue-100 border-blue-600': permits.includes({{ $permit->id }}),
                                                    'bg-white text-slate-600 border-slate-100 hover:border-blue-200': !permits.includes({{ $permit->id }})
                                                }"
                                                class="px-4 py-3 rounded-xl border-2 text-[11px] font-black transition-all flex items-center gap-3 text-left"
                                            >
                                                <span class="opacity-50 shrink-0">#</span>
                                                <span>{{ $permit->label }}</span>
                                            </button>
                                        @endforeach
                                    </div>
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
                                            class="block w-full px-6 py-5 bg-slate-50 border-2 border-slate-50 rounded-2xl text-2xl font-black text-slate-800 placeholder-slate-300 focus:bg-white focus:border-blue-500 focus:ring-0 transition-all outline-none"
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
    <script>
        function mobilityApp(config) {
            return {
                zip_code: config.zip_code,
                radius: config.radius,
                permits: config.permits,
                contract_preferences: config.contract_preferences || [],
                isSaving: false,
                showSuccess: false,

                togglePermit(id) {
                    if (id === config.nonePermitId) {
                        this.permits = [id];
                    } else {
                        if (this.permits.includes(config.nonePermitId)) {
                            this.permits = this.permits.filter(p => p !== config.nonePermitId);
                        }
                        
                        if (this.permits.includes(id)) {
                            this.permits = this.permits.filter(p => p !== id);
                        } else {
                            this.permits.push(id);
                        }
                    }
                    this.save();
                },

                toggleContract(type) {
                    if (this.contract_preferences.includes(type)) {
                        this.contract_preferences = this.contract_preferences.filter(t => t !== type);
                    } else {
                        this.contract_preferences.push(type);
                    }
                    this.save();
                },

                async save() {
                    this.isSaving = true;
                    this.showSuccess = false;

                    try {
                        const response = await fetch(config.routes.update, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': config.csrfToken
                            },
                            body: JSON.stringify({
                                zip_code: this.zip_code,
                                radius: this.radius,
                                permits: this.permits,
                                contract_preferences: this.contract_preferences
                            })
                        });

                        if (response.ok) {
                            this.showSuccess = true;
                            setTimeout(() => this.showSuccess = false, 3000);
                        }
                    } catch (error) {
                        console.error('Save failed:', error);
                    } finally {
                        this.isSaving = false;
                    }
                }
            }
        }
    </script>
</x-app-layout>
