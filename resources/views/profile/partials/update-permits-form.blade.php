<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Permis de Conduire & Mobilité') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Sélectionnez les permis de conduire que vous possédez.") }}
        </p>
    </header>

    @php
        $initialPermits = $user->permits->map(function($p) {
            return [
                'id' => $p->id,
                'label' => $p->label
            ];
        })->values();
        
        $availablePermits = $allPermits->map(function($p) {
            return [
                'id' => $p->id,
                'label' => $p->label
            ];
        })->values();
    @endphp

    <div x-data='{
        search: "",
        isSaving: false,
        selectedItems: @json($initialPermits),
        allAvailable: @json($availablePermits),

        get filteredAvailable() {
            const query = this.search.toLowerCase();
            return this.allAvailable.filter(item => {
                const matchesSearch = item.label.toLowerCase().includes(query);
                const notSelected = !this.selectedItems.find(s => s.id === item.id);
                return matchesSearch && notSelected;
            });
        },

        async save() {
            this.isSaving = true;
            try {
                const response = await fetch("{{ route("profile.permits.update") }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({
                        _method: "PATCH",
                        permits: this.selectedItems.map(i => i.id)
                    })
                });
                if (!response.ok) throw new Error("Erreur");
            } catch (e) {
                console.error(e);
            } finally {
                setTimeout(() => { this.isSaving = false; }, 600);
            }
        },

        addItem(item) {
            this.selectedItems.push(item);
            this.save();
            this.search = "";
        },

        removeItem(id) {
            this.selectedItems = this.selectedItems.filter(i => i.id !== id);
            this.save();
        }
    }'>
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-xl font-black text-gray-900 tracking-tight">{{ __('Permis & Véhicules') }}</h2>
                <p class="text-xs text-gray-500 mt-1 italic">{{ __('Quels types de véhicules pouvez-vous conduire ?') }}</p>
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
                        <div class="w-2 h-2 bg-yellow-400 rounded-full mr-2"></div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">À jour</span>
                    </div>
                </template>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            {{-- Sélectionnées --}}
            <div class="lg:col-span-8">
                <div class="flex flex-wrap gap-3">
                    <template x-for="item in selectedItems" :key="item.id">
                        <div class="flex items-center gap-3 px-4 py-3 bg-yellow-50 text-yellow-900 rounded-2xl border border-yellow-100 shadow-sm animate-fade-in group hover:bg-yellow-100 transition-colors">
                            <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                            <span class="text-sm font-black" x-text="item.label"></span>
                            <button type="button" @click="removeItem(item.id)" class="text-yellow-400 hover:text-red-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </template>
                </div>
                <template x-if="selectedItems.length === 0">
                    <div class="py-12 text-center bg-gray-50 rounded-3xl border-2 border-dashed border-gray-100">
                        <p class="text-sm text-gray-400 italic">{{ __('Aucun permis spécifié.') }}</p>
                    </div>
                </template>
            </div>

            {{-- Recherche Sidebar --}}
            <div class="lg:col-span-4 bg-gray-50/50 p-6 rounded-3xl border border-gray-100 shadow-inner">
                <h4 class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-4">{{ __('Ajouter un permis') }}</h4>
                <div class="relative mb-4">
                    <x-text-input 
                        x-model="search" 
                        type="text" 
                        placeholder="Ex: Permis B..." 
                        class="w-full text-xs pl-9 bg-white border-none shadow-sm rounded-xl focus:ring-2 focus:ring-yellow-500"
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
                            class="flex items-center justify-between p-3 bg-white hover:bg-yellow-500 hover:text-white rounded-xl border border-gray-100 shadow-sm cursor-pointer transition-all group"
                        >
                            <span class="text-xs font-bold" x-text="item.label"></span>
                            <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</section>
