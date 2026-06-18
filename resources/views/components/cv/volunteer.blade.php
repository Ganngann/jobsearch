<div class="flex items-center justify-between group">
    <div class="cv-section-title mb-0">Engagement Associatif</div>
    <button @click="startCreating('volunteer')" aria-label="Ajouter une expérience bénévole" title="Ajouter une expérience bénévole" class="opacity-0 group-hover:opacity-100 focus-within:opacity-100 focus:opacity-100 transition-opacity text-indigo-600 hover:text-indigo-800 mb-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
    </button>
</div>
<div class="space-y-2 mb-2">
    <template x-for="vol in volunteer_experiences" :key="vol.id">
        <div class="cv-item group relative" :class="(vol.status === 'draft' || vol.proposed_action) ? 'cv-item-draft' : ''">
            <template x-if="editingItem.id !== vol.id || editingItem.type !== 'volunteer'">
                <div @dblclick="startEditing('volunteer', vol)" class="cursor-pointer">
                        <div class="flex items-center gap-2">
                            <h4 class="text-[11px] font-bold text-gray-900">
                                <template x-if="vol.proposed_action === 'update' && vol.proposed_data?.role">
                                    <span x-html="renderDiff(vol.role, vol.proposed_data.role)"></span>
                                </template>
                                <template x-if="vol.proposed_action !== 'update' || !vol.proposed_data?.role">
                                    <span x-text="vol.role"></span>
                                </template>
                            </h4>
                            <span class="text-[8px] text-gray-300 font-normal">#<span x-text="vol.id"></span></span>
                        </div>
                        <p class="text-[10px] text-gray-500">
                            <template x-if="vol.proposed_action === 'update' && vol.proposed_data?.organization">
                                <span x-html="renderDiff(vol.organization, vol.proposed_data.organization)"></span>
                            </template>
                            <template x-if="vol.proposed_action !== 'update' || !vol.proposed_data?.organization">
                                <span x-text="vol.organization"></span>
                            </template>
                        </p>
                        <div class="text-[10px] text-gray-400 mt-1" x-show="vol.description || vol.proposed_data?.description">
                            <template x-if="vol.proposed_action === 'update' && vol.proposed_data?.description">
                                <div class="whitespace-pre-line" x-html="renderDiff(vol.description, vol.proposed_data.description)"></div>
                            </template>
                            <template x-if="vol.proposed_action !== 'update' || !vol.proposed_data?.description">
                                <div class="whitespace-pre-line" x-text="(vol.description || '').trim()"></div>
                            </template>
                        </div>

                        <!-- Actions pour les suggestions -->
                        <template x-if="vol.proposed_action">
                            <div class="mt-2 flex gap-2">
                                <button @click.stop="acceptItem('volunteer', vol.id)" class="text-[8px] px-2 py-0.5 bg-indigo-600 text-white rounded font-bold">Accepter</button>
                                <button @click.stop="rejectItem('volunteer', vol.id)" class="text-[8px] px-2 py-0.5 bg-white border border-gray-200 text-gray-500 rounded font-bold">Refuser</button>
                            </div>
                        </template>
                </div>
            </template>
            <template x-if="editingItem.id === vol.id && editingItem.type === 'volunteer'">
                <div class="space-y-2 bg-white p-2 rounded shadow-sm border border-indigo-100">
                    <input type="text" x-model="editingData.role" class="w-full text-[10px] font-bold border-gray-200 rounded p-1" placeholder="Rôle">
                    <input type="text" x-model="editingData.organization" class="w-full text-[9px] border-gray-200 rounded p-1" placeholder="Organisation">
                    <div class="flex gap-2">
                        <input type="date" x-model="editingData.start_date" class="flex-1 text-[9px] border-gray-200 rounded p-1">
                        <input type="date" x-model="editingData.end_date" class="flex-1 text-[9px] border-gray-200 rounded p-1">
                    </div>
                    <textarea x-model="editingData.description" class="w-full text-[9px] border-gray-200 rounded p-1" rows="2" placeholder="Description"></textarea>
                    <div class="flex justify-end gap-1">
                        <button @click="deleteItem('volunteer', vol.id)" class="text-[8px] text-red-400 mr-auto hover:text-red-600">Supprimer</button>
                        <button @click="cancelEdit()" class="text-[8px] text-gray-400">Annuler</button>
                        <button @click="saveManualEdit()" class="text-[8px] bg-indigo-600 text-white px-2 py-0.5 rounded">OK</button>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>
