<div class="flex items-center justify-between group">
    <div class="cv-section-title mb-0">Points Forts & Atouts</div>
    <button @click="startCreating('fact')" aria-label="Ajouter un atout" title="Ajouter un atout" class="opacity-0 group-hover:opacity-100 focus-within:opacity-100 focus:opacity-100 transition-opacity text-indigo-600 hover:text-indigo-800 mb-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
    </button>
</div>
<div class="grid grid-cols-1 gap-1.5">
    <template x-for="fact in filteredFacts" :key="fact.id">
        <div class="relative group" :class="fact.proposed_action ? 'bg-amber-50 p-2 rounded-lg border-2 border-amber-200' : ''">
            <template x-if="editingItem.id !== fact.id || editingItem.type !== 'fact'">
                <div @dblclick="startEditing('fact', fact)" class="flex gap-2 items-start cursor-pointer relative group">
                    <div class="w-1.5 h-1.5 bg-indigo-400 rounded-full mt-1.5 flex-shrink-0" :class="fact.proposed_action ? 'bg-amber-400' : ''"></div>
                    <div class="flex-1">
                        <template x-if="fact.proposed_action === 'update'">
                            <div class="space-y-1 mb-1">
                                <p class="text-[11px] text-amber-900 leading-relaxed inline font-semibold">
                                    <span class="font-bold text-[9px] uppercase text-amber-500 mr-1" x-text="fact.category"></span>
                                    <span class="text-[8px] text-gray-300 font-normal mr-1">#<span x-text="fact.local_id"></span></span>
                                    <span x-html="renderDiff(fact.content, fact.proposed_content)"></span>
                                </p>
                            </div>
                        </template>
                        <template x-if="fact.proposed_action !== 'update'">
                            <p class="text-[11px] text-gray-700 leading-relaxed inline" :class="fact.proposed_action === 'delete' ? 'line-through text-red-400' : (fact.proposed_action === 'add' ? 'text-indigo-700 font-medium' : '')">
                                <span class="font-bold text-[9px] uppercase text-gray-400 mr-1" x-text="fact.category"></span>
                                <span class="text-[8px] text-gray-300 font-normal mr-1">#<span x-text="fact.local_id"></span></span>
                                <span :class="fact.proposed_action === 'add' ? 'diff-added' : (fact.proposed_action === 'delete' ? 'diff-deleted' : '')" x-text="fact.content"></span>
                            </p>
                        </template>

                        <!-- Buttons for Proposed Actions -->
                        <template x-if="fact.proposed_action">
                            <div class="flex gap-2 mt-2">
                                <button @click.stop="acceptFact(fact.id)" class="text-[9px] bg-indigo-600 text-white px-2 py-1 rounded font-bold hover:bg-indigo-700 transition shadow-sm">Accepter</button>
                                <button @click.stop="rejectFact(fact.id)" class="text-[9px] bg-white border border-gray-200 text-gray-500 px-2 py-1 rounded font-bold hover:bg-gray-50 transition shadow-sm">Refuser</button>
                            </div>
                        </template>
                        
                        <button x-show="!fact.proposed_action" @click.stop="deleteItem('fact', fact.id)" 
                                class="inline-flex items-center ml-2 p-0.5 opacity-0 group-hover:opacity-100 focus-within:opacity-100 focus:opacity-100 transition-opacity text-red-400 hover:text-red-600 align-middle">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </template>

            <template x-if="editingItem.id === fact.id && editingItem.type === 'fact'">
                <div class="space-y-2">
                    <select x-model="editingData.category" class="w-full text-[10px] border-gray-200 rounded p-1">
                        <option value="VALEURS">VALEURS</option>
                        <option value="OBJECTIFS">OBJECTIFS</option>
                        <option value="SOFT_SKILLS">SOFT SKILLS</option>
                        <option value="PREFERENCES">PRÉFÉRENCES</option>
                    </select>
                    <textarea x-model="editingData.content" class="w-full text-[11px] border-gray-200 rounded p-1" rows="2"></textarea>
                    <div class="flex justify-end gap-2">
                        <button @click="deleteItem('fact', fact.id)" class="text-[9px] text-red-400 mr-auto hover:text-red-600">Supprimer</button>
                        <button @click="cancelEdit()" class="text-[9px] text-gray-400">Annuler</button>
                        <button @click="saveManualEdit()" class="text-[9px] bg-indigo-600 text-white px-3 py-1 rounded">OK</button>
                    </div>
                </div>
            </template>

        </div>
    </template>
</div>
