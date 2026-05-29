<div class="cv-header">
    <template x-if="editingItem.type !== 'user'">
        <div @dblclick="startEditingUser()" class="flex justify-between items-end cursor-pointer group relative">
            <div>
                <h1 class="text-2xl font-black text-gray-900 tracking-tight leading-none" x-text="user.name"></h1>
                <p class="text-indigo-600 font-bold text-[11px] uppercase tracking-[0.2em] mt-3" x-text="user.headline || 'Candidat à l\'emploi'"></p>
            </div>
            <div class="text-right text-[10px] text-gray-500 space-y-0.5">
                <p x-text="user.email"></p>
                <p x-text="user.phone || 'Téléphone non renseigné'"></p>
                <p x-show="user.birth_date" x-text="'Né(e) le ' + formatDate(user.birth_date)"></p>
                <div class="flex justify-end flex-wrap gap-x-3 gap-y-1 mt-2">
                    <template x-for="link in (user.links || [])" :key="link.url">
                        <a :href="link.url" target="_blank" class="text-gray-400 hover:text-indigo-600 transition-colors">
                            <span class="font-bold" x-text="link.label"></span>
                        </a>
                    </template>
                </div>
            </div>
            <div class="absolute -right-2 top-0 opacity-0 group-hover:opacity-100 focus-within:opacity-100 focus:opacity-100 transition-opacity p-1 bg-gray-100 rounded text-[8px] text-gray-400">Éditer profil</div>
        </div>
    </template>

    <template x-if="editingItem.type === 'user'">
        <div class="bg-gray-50 p-4 rounded-lg border border-indigo-100 space-y-3">
            <div class="grid grid-cols-2 gap-4">
                <input type="text" x-model="editingData.name" class="w-full text-sm border-gray-200 rounded" placeholder="Nom">
                <input type="email" x-model="editingData.email" class="w-full text-sm border-gray-200 rounded" placeholder="Email">
                <input type="text" x-model="editingData.phone" class="w-full text-sm border-gray-200 rounded" placeholder="Téléphone">
                <input type="date" x-model="editingData.birth_date" class="w-full text-sm border-gray-200 rounded" placeholder="Date de naissance">
            </div>
            <input type="text" x-model="editingData.headline" class="w-full text-sm border-gray-200 rounded" placeholder="Titre (ex: Développeur Fullstack)">
            <textarea x-model="editingData.profile_text" class="w-full text-[10px] border-gray-200 rounded p-2" rows="3" placeholder="Résumé / À propos de vous"></textarea>
            <textarea x-model="editingData.aspirations" class="w-full text-[10px] border-gray-200 rounded p-2" rows="2" placeholder="Mes aspirations professionnelles"></textarea>
            
            <!-- Link Repeater -->
            <div class="space-y-2">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Liens (GitHub, LinkedIn, etc.)</p>
                <template x-for="(link, index) in editingData.links" :key="index">
                    <div class="flex gap-2">
                        <input type="text" x-model="link.label" class="w-24 text-[10px] border-gray-200 rounded" placeholder="Label">
                        <input type="text" x-model="link.url" class="flex-1 text-[10px] border-gray-200 rounded" placeholder="URL">
                        <button @click="removeLink(index)" class="text-red-400 hover:text-red-600" aria-label="Supprimer le lien" title="Supprimer le lien">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </template>
                <button @click="addLink()" class="text-[10px] text-indigo-600 font-bold flex items-center gap-1 hover:text-indigo-800 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Ajouter un lien
                </button>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                <button @click="cancelEdit()" class="text-xs text-gray-400">Annuler</button>
                <button @click="saveUserEdit()" class="text-xs bg-indigo-600 text-white px-4 py-1.5 rounded font-bold transition hover:bg-indigo-700">Enregistrer</button>
            </div>
        </div>
    </template>
</div>
