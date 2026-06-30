<div class="mt-6">
    <div class="flex items-center justify-between group mb-2">
        <div class="cv-section-title mb-0">Centres d'intérêt</div>
        <button @click="startCreating('interest')" class="opacity-0 group-hover:opacity-100 focus-within:opacity-100 focus:opacity-100 transition-opacity text-indigo-600 hover:text-indigo-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        </button>
    </div>
    <div class="flex flex-wrap gap-2">
        <template x-for="interest in interests" :key="interest.id">
            <div class="relative group" :class="(interest.status === 'draft' || interest.proposed_action) ? 'cv-item-draft' : ''">
                <template x-if="editingItem.id !== interest.id || editingItem.type !== 'interest'">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <span @dblclick="startEditing('interest', interest)"
                                  class="text-[9px] font-bold uppercase tracking-wider px-3 py-1 rounded-full border cursor-pointer hover:border-indigo-300 transition-all" 
                                  :class="{
                                      'text-gray-400 bg-gray-50 border-gray-100': !interest.proposed_action && interest.status !== 'draft',
                                      'text-amber-600 bg-amber-50 border-amber-200': interest.proposed_action === 'add' || interest.status === 'draft',
                                      'text-red-600 bg-red-50 border-red-200 line-through': interest.proposed_action === 'delete',
                                      'text-indigo-600 bg-indigo-50 border-indigo-200': interest.proposed_action === 'update'
                                  }">
                                <template x-if="interest.proposed_action === 'update' && interest.proposed_data?.name">
                                    <span x-html="renderDiff(interest.name, interest.proposed_data.name)"></span>
                                </template>
                                <template x-if="interest.proposed_action !== 'update' || !interest.proposed_data?.name">
                                    <span x-text="interest.name"></span>
                                </template>
                            </span>
                        </div>
                        
                        <!-- Actions pour les suggestions -->
                        <template x-if="interest.proposed_action">
                            <div class="flex gap-1">
                                <button @click.stop="acceptItem('interest', interest.id)" class="text-[7px] px-1.5 py-0.5 bg-indigo-600 text-white rounded font-black uppercase tracking-tighter">OK</button>
                                <button @click.stop="rejectItem('interest', interest.id)" class="text-[7px] px-1.5 py-0.5 bg-white border border-gray-200 text-gray-400 rounded font-black uppercase tracking-tighter">Non</button>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="editingItem.id === interest.id && editingItem.type === 'interest'">
                    <div class="flex items-center gap-1 bg-white p-1 rounded shadow-sm border border-indigo-100">
                        <input type="text" x-model="editingData.name" @keyup.enter="saveManualEdit()"
                               class="text-[9px] font-bold uppercase border-gray-200 rounded-full px-2 py-0.5 w-32">
                        <button @click="deleteItem('interest', interest.id)" class="p-1 text-red-400 hover:text-red-600" title="Supprimer" aria-label="Supprimer">
                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                        <button @click="cancelEdit()" class="text-[8px] text-gray-400 font-bold px-1">X</button>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>
