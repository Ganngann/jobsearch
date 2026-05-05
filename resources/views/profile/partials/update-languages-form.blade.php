<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Langues & Communication') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Indiquez les langues que vous maîtrisez et votre niveau d'aisance.") }}
        </p>
    </header>

    @php
        $initialLanguages = $user->languages->map(function($l) {
            return [
                'id' => $l->id,
                'label' => $l->label,
                'level' => $l->pivot->level ?? '',
                'code' => $l->code
            ];
        })->values();
        
        $availableLanguages = $allLanguages->map(function($l) {
            return [
                'id' => $l->id,
                'label' => $l->label,
                'code' => $l->code
            ];
        })->values();
    @endphp

    <div x-data="languagesForm({
        selectedItems: {{ Js::from($initialLanguages) }},
        allAvailable: {{ Js::from($availableLanguages) }},
        csrfToken: {{ Js::from(csrf_token()) }},
        route: {{ Js::from(route('profile.languages.update')) }}
    })">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-xl font-black text-gray-900 tracking-tight">{{ __('Langues & Communication') }}</h2>
                <p class="text-xs text-gray-500 mt-1 italic">{{ __('Indiquez votre aisance linguistique.') }}</p>
            </div>
            <div class="flex items-center bg-gray-50 px-3 py-1.5 rounded-full border border-gray-100">
                <template x-if="isSaving">
                    <div class="flex items-center">
                        <div class="w-2 h-2 bg-amber-400 rounded-full animate-ping mr-2"></div>
                        <span class="text-[10px] font-bold text-amber-600 uppercase tracking-tighter">Sync...</span>
                    </div>
                </template>
                <template x-if="!isSaving">
                    <div class="flex items-center">
                        <div class="w-2 h-2 bg-indigo-400 rounded-full mr-2"></div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">À jour</span>
                    </div>
                </template>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            {{-- Sélectionnées --}}
            <div class="lg:col-span-8 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <template x-for="item in selectedItems" :key="item.id">
                        <div class="flex flex-col p-4 bg-white border border-gray-100 rounded-2xl shadow-sm hover:shadow-md transition-all group">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center">
                                    <template x-if="item.code && item.code.length < 5">
                                        <span class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-[10px] font-black mr-2" x-text="item.code.toUpperCase()"></span>
                                    </template>
                                    <span class="text-sm font-black text-gray-800" x-text="item.label"></span>
                                </div>
                                <button type="button" @click="removeItem(item.id)" class="text-gray-300 hover:text-red-500 transition-colors p-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                            <x-text-input 
                                x-model="item.level" 
                                @change="save()"
                                type="text" 
                                placeholder="Niveau (ex: B2, Natif...)" 
                                class="w-full text-xs py-2 px-3 bg-gray-50 border-none rounded-xl focus:ring-1 focus:ring-indigo-500" 
                            />
                        </div>
                    </template>
                </div>
                <template x-if="selectedItems.length === 0">
                    <div class="py-12 text-center bg-gray-50 rounded-3xl border-2 border-dashed border-gray-100">
                        <p class="text-sm text-gray-400 italic">{{ __('Aucune langue ajoutée.') }}</p>
                    </div>
                </template>
            </div>

            {{-- Recherche Sidebar --}}
            <div class="lg:col-span-4 bg-gray-50/50 p-6 rounded-3xl border border-gray-100 shadow-inner">
                <h4 class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-4">{{ __('Ajouter une langue') }}</h4>
                <div class="relative mb-4">
                    <x-text-input 
                        x-model="search" 
                        type="text" 
                        placeholder="Chercher..." 
                        class="w-full text-xs pl-9 bg-white border-none shadow-sm rounded-xl focus:ring-2 focus:ring-indigo-500"
                        autocomplete="off"
                    />
                    <div class="absolute left-3 top-2.5 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>
                <div class="max-h-60 overflow-y-auto space-y-1.5 pr-1 custom-scrollbar">
                    <template x-for="item in filteredAvailable" :key="item.id">
                        <div 
                            @click="addItem(item)"
                            class="flex items-center justify-between p-3 bg-white hover:bg-indigo-600 hover:text-white rounded-xl border border-gray-100 shadow-sm cursor-pointer transition-all group"
                        >
                            <span class="text-xs font-bold" x-text="item.label"></span>
                            <template x-if="item.code && item.code.length < 5">
                                <span class="text-[8px] font-black opacity-40 group-hover:opacity-100" x-text="item.code.toUpperCase()"></span>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</section>
