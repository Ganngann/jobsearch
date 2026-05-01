<section x-data='{
    zip_code: "{{ $user->zip_code }}",
    radius: {{ $user->radius ?? 20 }},
    isSaving: false,

    async save() {
        this.isSaving = true;
        try {
            const response = await fetch("{{ route("profile.mobility.update") }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    _method: "PATCH",
                    zip_code: this.zip_code,
                    radius: this.radius
                })
            });
            if (!response.ok) throw new Error("Erreur");
        } catch (e) {
            console.error(e);
        } finally {
            setTimeout(() => { this.isSaving = false; }, 600);
        }
    }
}'>
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-xl font-black text-gray-900 tracking-tight">{{ __('Mobilité Géographique') }}</h2>
            <p class="text-xs text-gray-500 mt-1 italic">{{ __('Où souhaitez-vous travailler ?') }}</p>
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
                    <div class="w-2 h-2 bg-green-400 rounded-full mr-2"></div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">À jour</span>
                </div>
            </template>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
        <div class="md:col-span-4">
            <x-input-label for="zip_code" :value="__('Code Postal de base')" class="text-xs font-bold uppercase text-gray-400 mb-2" />
            <x-text-input 
                x-model="zip_code" 
                @input.debounce.500ms="save()"
                id="zip_code" 
                type="text" 
                class="w-full bg-white border-gray-100 shadow-sm focus:ring-indigo-500 text-lg font-bold py-4 px-6 rounded-2xl" 
                placeholder="ex: 5000" 
            />
        </div>

        <div class="md:col-span-8">
            <div class="flex justify-between items-end mb-4">
                <x-input-label :value="__('Rayon de recherche')" class="text-xs font-bold uppercase text-gray-400" />
                <span class="text-2xl font-black text-indigo-600" x-text="radius + ' km'"></span>
            </div>
            <div class="relative pt-1">
                <input 
                    x-model="radius" 
                    @change="save()"
                    type="range" 
                    min="0" 
                    max="200" 
                    step="5" 
                    class="w-full h-3 bg-gray-100 rounded-full appearance-none cursor-pointer accent-indigo-600"
                >
                <div class="flex justify-between text-[10px] text-gray-300 font-bold mt-2 px-1">
                    <span>0 km</span>
                    <span>50 km</span>
                    <span>100 km</span>
                    <span>150 km</span>
                    <span>200 km</span>
                </div>
            </div>
        </div>
    </div>
</section>
