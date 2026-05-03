<div class="flex items-center justify-between group">
    <div class="cv-section-title mb-0">Certifications</div>
    <button @click="startCreating('certification')" class="opacity-0 group-hover:opacity-100 transition-opacity text-indigo-600 hover:text-indigo-800 mb-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
    </button>
</div>
<div class="space-y-1 mb-2">
    <template x-for="cert in certifications" :key="cert.id">
        <div class="relative group" :class="cert.status === 'draft' ? 'cv-item-draft' : ''">
            <template x-if="editingItem.id !== cert.id || editingItem.type !== 'certification'">
                <div @dblclick="startEditing('certification', cert)" class="flex items-center gap-2 cursor-pointer group relative">
                    <div class="w-1.5 h-1.5 bg-yellow-400 rounded-full"></div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <p class="text-[11px] font-bold text-gray-700" x-text="cert.name"></p>
                            <span class="text-[8px] text-gray-300 font-normal">#<span x-text="cert.id"></span></span>
                            <span class="text-[10px] text-gray-400" x-show="cert.issuing_organization" x-text="' — ' + cert.issuing_organization"></span>
                        </div>
                        <p class="text-[9px] text-gray-400" x-show="cert.issue_date" x-text="new Date(cert.issue_date).toLocaleDateString()"></p>
                    </div>
                </div>
            </template>
            <template x-if="editingItem.id === cert.id && editingItem.type === 'certification'">
                <div class="space-y-2 bg-gray-50 p-2 rounded border border-indigo-100">
                    <input type="text" x-model="editingData.name" class="w-full text-[10px] border-gray-200 rounded p-1" placeholder="Nom">
                    <input type="text" x-model="editingData.issuing_organization" class="w-full text-[10px] border-gray-200 rounded p-1" placeholder="Organisme">
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="text-[8px] text-gray-400">Date d'obtention</label>
                            <input type="date" x-model="editingData.issue_date" class="w-full text-[9px] border-gray-200 rounded p-1">
                        </div>
                        <div class="flex-1">
                            <label class="text-[8px] text-gray-400">Date d'expiration</label>
                            <input type="date" x-model="editingData.expiration_date" class="w-full text-[9px] border-gray-200 rounded p-1">
                        </div>
                    </div>
                    <div class="flex gap-1">
                        <button @click="cancelEdit()" class="text-[9px] text-gray-400 ml-auto">Annuler</button>
                        <button @click="saveManualEdit()" class="text-[9px] bg-indigo-600 text-white px-2 py-0.5 rounded">OK</button>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>
