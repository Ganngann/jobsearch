<div class="flex items-center justify-between group">
    <div class="cv-section-title mb-0">Formations</div>
    <button @click="startCreating('education')" aria-label="Ajouter une formation" title="Ajouter une formation" class="opacity-0 group-hover:opacity-100 focus-within:opacity-100 focus:opacity-100 transition-opacity text-indigo-600 hover:text-indigo-800 mb-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
    </button>
</div>
<div class="space-y-2">
    <template x-for="edu in all_educations" :key="edu.id">
        <div class="cv-item flex gap-3" :class="(edu.status === 'draft' || edu.proposed_action) ? 'cv-item-draft' : ''">
            <div class="cv-date pt-0.5">
                <template x-if="edu.start_date">
                    <span x-text="new Date(edu.start_date).getFullYear() + ' — '"></span>
                </template>
                <template x-if="edu.proposed_action === 'update' && edu.proposed_data.graduation_year">
                    <span class="text-blue-600 font-bold" x-text="edu.proposed_data.graduation_year"></span>
                </template>
                <template x-if="!(edu.proposed_action === 'update' && edu.proposed_data.graduation_year)">
                    <span x-text="edu.graduation_year"></span>
                </template>
            </div>
            <div class="cv-content">
                <template x-if="editingItem.id !== edu.id || editingItem.type !== 'education'">
                    <div @dblclick="startEditing('education', edu)" class="cursor-pointer group relative">
                        <div class="flex justify-between items-baseline">
                            <div class="flex-1">
                                <template x-if="edu.proposed_action === 'delete'">
                                    <div class="text-[10px] text-red-500 font-bold mb-0.5 uppercase tracking-tight">Suppression suggérée</div>
                                </template>
                                <h3 class="text-[12px] font-bold text-gray-900 leading-tight">
                                    <template x-if="edu.proposed_action === 'update' && edu.proposed_data && edu.proposed_data.degree">
                                        <span x-html="renderDiff(edu.degree, edu.proposed_data.degree)"></span>
                                    </template>
                                    <template x-if="edu.proposed_action === 'add'">
                                        <span class="diff-added" x-text="edu.degree"></span>
                                    </template>
                                    <template x-if="edu.proposed_action === 'delete'">
                                        <span class="diff-deleted" x-text="edu.degree"></span>
                                    </template>
                                    <template x-if="!edu.proposed_action || (edu.proposed_action === 'update' && !edu.proposed_data?.degree)">
                                        <span x-text="edu.degree"></span>
                                    </template>
                                    <span class="text-[9px] text-gray-300 font-normal ml-1">#<span x-text="edu.id"></span></span>

                                    <template x-if="edu.field || (edu.proposed_action === 'update' && edu.proposed_data && edu.proposed_data.field)">
                                        <span class="text-[11px] text-gray-500 font-normal ml-2">
                                            — 
                                            <template x-if="edu.proposed_action === 'update' && edu.proposed_data && edu.proposed_data.field">
                                                <span x-html="renderDiff(edu.field, edu.proposed_data.field)"></span>
                                            </template>
                                            <template x-if="!(edu.proposed_action === 'update' && edu.proposed_data && edu.proposed_data.field)">
                                                <span x-text="edu.field"></span>
                                            </template>
                                        </span>
                                    </template>
                                </h3>
                                <button @click.stop="deleteItem('education', edu.id)" class="opacity-0 group-hover:opacity-100 focus-within:opacity-100 focus:opacity-100 transition-opacity text-red-400 hover:text-red-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                            <span class="text-[11px] font-semibold text-gray-500">
                                <template x-if="edu.proposed_action === 'update' && edu.proposed_data && edu.proposed_data.school">
                                    <span x-html="renderDiff(edu.school, edu.proposed_data.school)"></span>
                                </template>
                                <template x-if="!(edu.proposed_action === 'update' && edu.proposed_data && edu.proposed_data.school)">
                                    <span x-text="edu.school"></span>
                                </template>
                            </span>
                        </div>
                        
                        <template x-if="edu.description || edu.proposed_data?.description">
                            <div class="mt-0.5 flex items-center gap-3">
                                <div class="text-[10.5px] text-gray-600 leading-relaxed flex-1">
                                    <template x-if="edu.proposed_action === 'update' && edu.proposed_data?.description"><div class="whitespace-pre-line" x-html="renderDiff(edu.description, edu.proposed_data.description)"></div></template>
                                    <template x-if="!(edu.proposed_action === 'update' && edu.proposed_data?.description)"><div class="whitespace-pre-line" x-text="(edu.description || '').trim()"></div></template>
                                </div>
                            </div>
                        </template>

                        <template x-if="edu.grade || (edu.proposed_action === 'update' && edu.proposed_data && edu.proposed_data.grade)">
                            <div class="px-1.5 py-0.5 bg-gray-100 text-gray-600 text-[9px] font-bold rounded border border-gray-200">
                                <template x-if="edu.proposed_action === 'update' && edu.proposed_data && edu.proposed_data.grade">
                                    <span x-html="renderDiff(edu.grade, edu.proposed_data.grade)"></span>
                                </template>
                                <template x-if="!(edu.proposed_action === 'update' && edu.proposed_data && edu.proposed_data.grade)">
                                    <span x-text="edu.grade"></span>
                                </template>
                            </div>
                        </template>
                        
                        <template x-if="edu.proposed_action">
                            <div class="mt-2 flex gap-2">
                                <button @click.stop="acceptItem('education', edu.id)" class="text-[9px] px-2 py-1 bg-indigo-600 text-white rounded font-bold">Accepter</button>
                                <button @click.stop="rejectItem('education', edu.id)" class="text-[9px] px-2 py-1 bg-white border border-gray-200 text-gray-500 rounded font-bold">Refuser</button>
                            </div>
                        </template>

                        <div class="mt-1 opacity-0 group-hover:opacity-100 focus-within:opacity-100 focus:opacity-100 transition-opacity text-[8px] text-gray-400">Double-clic pour éditer</div>
                    </div>
                </template>

                <template x-if="editingItem.id === edu.id && editingItem.type === 'education'">
                    <div class="space-y-2 bg-gray-50 p-3 rounded-lg border border-indigo-100">
                        <input type="text" x-model="editingData.degree" class="w-full text-[11px] font-bold border-gray-200 rounded p-1" placeholder="Diplôme">
                        <div class="flex gap-2">
                            <input type="text" x-model="editingData.school" class="flex-1 text-[10px] border-gray-200 rounded p-1" placeholder="École">
                            <input type="text" x-model="editingData.field" class="flex-1 text-[10px] border-gray-200 rounded p-1" placeholder="Sujet / Domaine">
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="text-[8px] text-gray-400">Début</label>
                                <input type="date" x-model="editingData.start_date" class="w-full text-[10px] border-gray-200 rounded p-1">
                            </div>
                            <div class="flex-1">
                                <label class="text-[8px] text-gray-400">Année diplôme</label>
                                <input type="number" x-model="editingData.graduation_year" class="w-full text-[10px] border-gray-200 rounded p-1" placeholder="Année">
                            </div>
                            <div class="flex-1">
                                <label class="text-[8px] text-gray-400">Note / Grade</label>
                                <input type="text" x-model="editingData.grade" class="w-full text-[10px] border-gray-200 rounded p-1" placeholder="ex: Mention TB">
                            </div>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button @click="cancelEdit()" class="text-[9px] text-gray-400">Annuler</button>
                            <button @click="saveManualEdit()" class="text-[9px] bg-indigo-600 text-white px-2 py-0.5 rounded font-bold">OK</button>
                        </div>
                    </div>
                </template>

                <template x-if="edu.status === 'draft' && !edu.proposed_action && editingItem.id !== edu.id">
                    <div class="mt-2 flex gap-2">
                        <button @click="acceptItem('education', edu.id)" class="text-[9px] px-3 py-1 bg-indigo-600 text-white rounded font-bold">Valider</button>
                        <button @click="deleteItem('education', edu.id)" class="text-[9px] px-3 py-1 bg-white border border-gray-200 text-gray-500 rounded">Supprimer</button>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>
