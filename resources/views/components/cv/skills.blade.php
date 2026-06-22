<div class="flex items-center gap-2 mt-2 mb-2 group">
    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Compétences Techniques</div>
    <button @click="startCreating('skill')" class="opacity-0 group-hover:opacity-100 focus-within:opacity-100 focus:opacity-100 focus-visible:opacity-100 transition-opacity text-indigo-600 hover:text-indigo-800" aria-label="Ajouter une compétence" title="Ajouter une compétence">
        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
    </button>
</div>
<div class="flex flex-wrap gap-2 mb-4">
    <template x-for="skill in skills" :key="skill.id">
        <div class="relative group">
            <template x-if="editingItem.id !== skill.id || editingItem.type !== 'skill'">
                <div class="relative">
                    <span @dblclick="startEditing('skill', skill)" 
                          class="text-[9px] font-bold bg-gray-900 text-white px-3 py-1 rounded tracking-wider uppercase cursor-pointer hover:bg-indigo-600 transition-colors"
                          x-text="skill.label"></span>
                    <button @click="deleteItem('skill', skill.id)" 
                            class="absolute -top-1.5 -right-1.5 bg-red-500 text-white rounded-full w-3.5 h-3.5 flex items-center justify-center opacity-0 group-hover:opacity-100 focus-within:opacity-100 focus:opacity-100 focus-visible:opacity-100 transition-opacity hover:bg-red-600 shadow-sm" aria-label="Supprimer" title="Supprimer">
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-2 w-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </template>
            <template x-if="editingItem.id === skill.id && editingItem.type === 'skill'">
                <input type="text" x-model="editingData.label" @keyup.enter="saveManualEdit()" @blur="saveManualEdit()"
                       class="text-[9px] font-bold uppercase bg-white border-indigo-600 px-2 py-1 rounded w-24">
            </template>
        </div>
    </template>
</div>
