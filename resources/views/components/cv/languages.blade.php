<div class="mt-4 group/lang">
    <div class="flex items-center justify-between mb-2">
        <div class="cv-section-title mb-0">Langues</div>
        <button @click="startCreating('language')" class="opacity-0 group-hover/lang:opacity-100 transition-opacity text-indigo-600 hover:text-indigo-800" aria-label="Ajouter une langue" title="Ajouter une langue">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
        </button>
    </div>

    <div class="flex flex-wrap gap-x-6 gap-y-3">
        <template x-for="lang in languages" :key="lang.id + '-' + lang.label">
            <div class="relative">
                <!-- Display Mode -->
                <template x-if="editingItem.id !== lang.id || editingItem.type !== 'language'">
                    <div @dblclick="startEditing('language', lang)" class="flex items-center gap-2 cursor-pointer hover:bg-indigo-50 px-2 py-1 rounded transition-colors group">
                        <span class="text-[10px] font-bold text-gray-900" x-text="lang.label"></span>
                        <span class="text-[9px] text-gray-400" x-text="lang.level"></span>
                        
                        <button @click.stop="deleteItem('language', lang.id)" class="opacity-0 group-hover:opacity-100 focus-within:opacity-100 focus:opacity-100 text-red-300 hover:text-red-500 ml-1" aria-label="Supprimer la langue" title="Supprimer la langue">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </template>

                <!-- Edit Mode -->
                <template x-if="editingItem.id === lang.id && editingItem.type === 'language'">
                    <div class="flex flex-col gap-1 p-2 bg-white border border-indigo-100 rounded shadow-sm z-20 min-w-[150px]">
                        <template x-if="lang._isNew">
                            <select x-model="editingData.label" class="text-[10px] border-gray-200 rounded p-1 w-full">
                                <template x-for="available in allAvailableLanguages" :key="available.id">
                                    <option :value="available.label" x-text="available.label" :selected="editingData.label === available.label"></option>
                                </template>
                            </select>
                        </template>
                        <template x-if="!lang._isNew">
                            <div class="text-[10px] font-bold text-indigo-600 mb-1" x-text="lang.label"></div>
                        </template>

                        <input type="text" x-model="editingData.level" class="text-[10px] border-gray-200 rounded p-1 w-full" placeholder="Ex: Maternelle, B2...">
                        
                        <div class="flex justify-end gap-1 mt-1">
                            <button @click="cancelEdit()" class="text-[8px] text-gray-400 px-1">Annuler</button>
                            <button @click="saveManualEdit()" class="text-[8px] bg-indigo-600 text-white px-2 py-0.5 rounded font-bold uppercase">OK</button>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>
