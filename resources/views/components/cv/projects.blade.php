<div class="flex items-center justify-between group">
    <div class="cv-section-title mb-0">Réalisations & Projets</div>
    <button @click="startCreating('project')" class="opacity-0 group-hover:opacity-100 transition-opacity text-indigo-600 hover:text-indigo-800 mb-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
    </button>
</div>
<div class="grid grid-cols-2 gap-x-4 gap-y-2">
    <template x-for="project in projects" :key="project.id">
        <div :class="(project.status === 'draft' || project.proposed_action) ? 'cv-item-draft' : ''" class="group relative">
            <template x-if="editingItem.id !== project.id || editingItem.type !== 'project'">
                <div @dblclick="startEditing('project', project)" class="cursor-pointer group relative">
                    <h4 class="text-[11px] font-bold text-gray-900">
                        <template x-if="project.proposed_action === 'update' && project.proposed_data && project.proposed_data.name">
                            <span x-html="renderDiff(project.name, project.proposed_data.name)"></span>
                        </template>
                        <template x-if="!(project.proposed_action === 'update' && project.proposed_data && project.proposed_data.name)">
                            <span x-text="project.name"></span>
                        </template>
                    </h4>
                    <div class="text-[10px] text-gray-500 mt-1 whitespace-pre-line">
                        <template x-if="project.proposed_action === 'update' && project.proposed_data && project.proposed_data.description">
                            <div x-html="renderDiff(project.description, project.proposed_data.description)"></div>
                        </template>
                        <template x-if="!(project.proposed_action === 'update' && project.proposed_data && project.proposed_data.description)">
                            <div x-text="project.description"></div>
                        </template>
                    </div>

                    <template x-if="project.url || (project.proposed_action === 'update' && project.proposed_data && project.proposed_data.url)">
                        <div class="mt-1 flex items-center gap-1 text-[9px] text-indigo-500">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                            <template x-if="project.proposed_action === 'update' && project.proposed_data && project.proposed_data.url">
                                <span x-html="renderDiff(project.url, project.proposed_data.url)"></span>
                            </template>
                            <template x-if="!(project.proposed_action === 'update' && project.proposed_data && project.proposed_data.url)">
                                <span x-text="project.url"></span>
                            </template>
                        </div>
                    </template>
                    
                    <template x-if="project.proposed_action">
                        <div class="mt-2 flex gap-2">
                            <button @click.stop="acceptItem('project', project.id)" class="text-[8px] px-2 py-0.5 bg-indigo-600 text-white rounded font-bold">Accepter</button>
                            <button @click.stop="rejectItem('project', project.id)" class="text-[8px] px-2 py-0.5 bg-white border border-gray-200 text-gray-500 rounded font-bold">Refuser</button>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="editingItem.id === project.id && editingItem.type === 'project'">
                <div class="space-y-2 bg-white p-2 rounded shadow-sm border border-indigo-100">
                    <input type="text" x-model="editingData.name" class="w-full text-[10px] font-bold border-gray-200 rounded p-1" placeholder="Nom du projet">
                    <input type="text" x-model="editingData.url" class="w-full text-[9px] border-gray-200 rounded p-1" placeholder="URL du projet">
                    <div class="flex gap-2">
                        <input type="date" x-model="editingData.start_date" class="flex-1 text-[9px] border-gray-200 rounded p-1">
                        <label class="flex items-center gap-1 text-[9px] text-gray-500">
                            <input type="checkbox" x-model="editingData.is_ongoing" class="rounded text-indigo-600"> En cours
                        </label>
                    </div>
                    <textarea x-model="editingData.description" class="w-full text-[9px] border-gray-200 rounded p-1" rows="2" placeholder="Description"></textarea>
                    <div class="flex justify-end gap-1">
                        <button @click="deleteItem('project', project.id)" class="text-[8px] text-red-400 mr-auto hover:text-red-600">Supprimer</button>
                        <button @click="cancelEdit()" class="text-[8px] text-gray-400">Annuler</button>
                        <button @click="saveManualEdit()" class="text-[8px] bg-indigo-600 text-white px-2 py-0.5 rounded">OK</button>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>
