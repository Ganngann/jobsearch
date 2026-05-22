<div class="flex items-center justify-between group">
    <div class="cv-section-title mb-0">Expériences Professionnelles</div>
    <button @click="startCreating('experience')" class="opacity-0 group-hover:opacity-100 focus-within:opacity-100 focus:opacity-100 transition-opacity text-indigo-600 hover:text-indigo-800 mb-2" aria-label="Ajouter une expérience">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
    </button>
</div>
<div class="space-y-2">
    <template x-for="exp in all_experiences" :key="exp.id">
        <div class="cv-item flex gap-3" :class="(exp.status === 'draft' || exp.proposed_action) ? 'cv-item-draft' : ''">
            <div class="cv-date pt-0.5">
                <span x-text="exp.start_date ? new Date(exp.start_date).getFullYear() : '?'"></span>
                — 
                <template x-if="exp.proposed_action === 'update' && (exp.proposed_data.is_current !== undefined || exp.proposed_data.end_date)">
                    <span class="text-blue-600 font-bold" x-text="exp.proposed_data.is_current ? 'Présent' : (exp.proposed_data.end_date ? new Date(exp.proposed_data.end_date).getFullYear() : '?')"></span>
                </template>
                <template x-if="!(exp.proposed_action === 'update' && (exp.proposed_data.is_current !== undefined || exp.proposed_data.end_date))">
                    <span x-text="exp.is_current ? 'Présent' : (exp.end_date ? new Date(exp.end_date).getFullYear() : '?')"></span>
                </template>
            </div>
            <div class="cv-content">
                <template x-if="editingItem.id !== exp.id || editingItem.type !== 'experience'">
                    <div @dblclick="startEditing('experience', exp)" class="cursor-pointer group relative">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <template x-if="exp.proposed_action === 'delete'">
                                    <div class="text-[9px] text-red-500 font-bold mb-0.5 uppercase tracking-tight">Suppression suggérée</div>
                                </template>
                                <h3 class="text-[11.5px] font-bold text-gray-900 leading-tight">
                                    <template x-if="exp.proposed_action === 'update' && exp.proposed_data?.title">
                                        <span x-html="renderDiff(exp.title, exp.proposed_data.title)"></span>
                                    </template>
                                    <template x-if="exp.proposed_action === 'add'">
                                        <span class="diff-added" x-text="exp.title || 'Poste'"></span>
                                    </template>
                                    <template x-if="exp.proposed_action === 'delete'">
                                        <span class="diff-deleted" x-text="exp.title || 'Poste'"></span>
                                    </template>
                                    <template x-if="!exp.proposed_action || (exp.proposed_action === 'update' && !exp.proposed_data?.title)">
                                        <span x-text="exp.title || 'Poste'"></span>
                                    </template>
                                    <span class="text-[9px] text-gray-300 font-normal ml-1">#<span x-text="exp.id"></span></span>
                                    
                                    <template x-if="exp.employment_type || (exp.proposed_action === 'update' && exp.proposed_data?.employment_type)">
                                        <span class="text-[9px] text-gray-400 font-normal ml-1.5 italic">
                                            — 
                                            <template x-if="exp.proposed_action === 'update' && exp.proposed_data?.employment_type">
                                                <span x-html="renderDiff(exp.employment_type, exp.proposed_data.employment_type)"></span>
                                            </template>
                                            <template x-if="!(exp.proposed_action === 'update' && exp.proposed_data?.employment_type)">
                                                <span x-text="exp.employment_type"></span>
                                            </template>
                                        </span>
                                    </template>
                                </h3>
                            </div>

                            <div class="flex items-center gap-2 ml-4">
                                <template x-if="exp.proposed_action">
                                    <div class="flex gap-1">
                                        <button @click.stop="acceptItem('experience', exp.id)" class="text-[8px] px-2 py-0.5 bg-indigo-600 text-white rounded font-bold uppercase">OK</button>
                                        <button @click.stop="rejectItem('experience', exp.id)" class="text-[8px] px-2 py-0.5 bg-white border border-gray-200 text-gray-400 rounded font-bold uppercase hover:text-red-500">X</button>
                                    </div>
                                </template>
                                <button @click.stop="deleteItem('experience', exp.id)" class="opacity-0 group-hover:opacity-100 focus-within:opacity-100 focus:opacity-100 transition-opacity text-red-300 hover:text-red-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="text-[11px] font-semibold text-gray-500 flex items-center gap-2">
                            <template x-if="exp.proposed_action === 'update' && exp.proposed_data?.company">
                                <span x-html="renderDiff(exp.company, exp.proposed_data.company)"></span>
                            </template>
                            <template x-if="!(exp.proposed_action === 'update' && exp.proposed_data?.company)">
                                <span x-text="exp.company || 'Entreprise'"></span>
                            </template>
                            <template x-if="exp.location || (exp.proposed_action === 'update' && exp.proposed_data?.location)">
                                <span class="text-[10px] text-gray-400 font-normal">
                                    • 
                                    <template x-if="exp.proposed_action === 'update' && exp.proposed_data?.location">
                                        <span x-html="renderDiff(exp.location, exp.proposed_data.location)"></span>
                                    </template>
                                    <template x-if="!(exp.proposed_action === 'update' && exp.proposed_data?.location)">
                                        <span x-text="exp.location"></span>
                                    </template>
                                </span>
                            </template>
                        </div>

                        <template x-if="exp.description || exp.proposed_data?.description">
                            <div class="text-[10.5px] text-gray-600 mt-0.5 leading-relaxed">
                                <template x-if="exp.proposed_action === 'update' && exp.proposed_data?.description"><div class="whitespace-pre-line" x-html="renderDiff(exp.description, exp.proposed_data.description)"></div></template>
                                <template x-if="!(exp.proposed_action === 'update' && exp.proposed_data?.description)"><div class="whitespace-pre-line" x-text="(exp.description || '').trim()"></div></template>
                            </div>
                        </template>
                        
                        <div class="mt-1 opacity-0 group-hover:opacity-100 focus-within:opacity-100 focus:opacity-100 transition-opacity text-[8px] text-gray-400">Double-clic pour éditer</div>
                    </div>
                </template>

                <template x-if="editingItem.id === exp.id && editingItem.type === 'experience'">
                    <div class="space-y-2 bg-gray-50 p-3 rounded-lg border border-indigo-100">
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" x-model="editingData.title" class="w-full text-[12px] font-bold border-gray-200 rounded p-1" placeholder="Poste">
                            <input type="text" x-model="editingData.company" class="w-full text-[11px] border-gray-200 rounded p-1" placeholder="Entreprise">
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <input type="date" x-model="editingData.start_date" class="text-[10px] border-gray-200 rounded p-1">
                            <input type="date" x-model="editingData.end_date" :disabled="editingData.is_current" class="text-[10px] border-gray-200 rounded p-1">
                            <label class="flex items-center gap-1 text-[10px] text-gray-500">
                                <input type="checkbox" x-model="editingData.is_current" class="rounded text-indigo-600"> En poste
                            </label>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" x-model="editingData.employment_type" class="text-[10px] border-gray-200 rounded p-1" placeholder="Type (CDI, Freelance...)">
                            <input type="text" x-model="editingData.location" class="text-[10px] border-gray-200 rounded p-1" placeholder="Lieu">
                        </div>
                        <textarea x-model="editingData.description" class="w-full text-[11px] border-gray-200 rounded p-1" rows="3" placeholder="Description"></textarea>
                        <div class="flex justify-end gap-2">
                            <button @click="deleteItem('experience', exp.id)" class="text-[10px] text-red-400 mr-auto hover:text-red-600">Supprimer</button>
                            <button @click="cancelEdit()" class="text-[10px] text-gray-400">Annuler</button>
                            <button @click="saveManualEdit()" class="text-[10px] bg-indigo-600 text-white px-3 py-1 rounded font-bold">Enregistrer</button>
                        </div>
                    </div>
                </template>
                
                <template x-if="exp.status === 'draft' && !exp.proposed_action && editingItem.id !== exp.id">
                    <div class="mt-3 flex gap-2">
                        <button @click="acceptItem('experience', exp.id)" class="text-[9px] px-3 py-1 bg-indigo-600 text-white rounded font-bold hover:bg-indigo-700 shadow-sm transition-all">Valider</button>
                        <button @click="deleteItem('experience', exp.id)" class="text-[9px] px-3 py-1 bg-white border border-gray-200 text-gray-500 rounded hover:bg-gray-50">Supprimer</button>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>
