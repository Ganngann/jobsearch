<section class="mt-4">
    <header class="mb-8">
        <h2 class="text-2xl font-black text-gray-900 tracking-tight">
            {{ __('Mes Récits & Expériences') }}
        </h2>
        <p class="mt-2 text-sm text-gray-500 max-w-2xl">
            {{ __("Ces faits constituent la base de votre profil. Chaque récit est lié à des compétences Forem spécifiques pour maximiser votre visibilité auprès des recruteurs.") }}
        </p>
    </header>

    <div x-data="manageFacts()" class="grid grid-cols-1 md:grid-cols-6 gap-4">
        <template x-for="(fact, index) in facts" :key="fact.id">
            <div :class="{
                    'md:col-span-4 md:row-span-2': index === 0,
                    'md:col-span-2 md:row-span-1': index !== 0 && fact.content.length < 150,
                    'md:col-span-3 md:row-span-1': index !== 0 && fact.content.length >= 150
                 }"
                 class="relative bg-white border border-gray-100 rounded-[2rem] p-6 shadow-sm hover:shadow-xl hover:border-indigo-100 transition-all duration-300 group flex flex-col justify-between overflow-hidden">
                
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-50/50 rounded-full blur-2xl group-hover:bg-indigo-100/50 transition-colors"></div>

                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-[10px] font-black uppercase tracking-widest text-indigo-400 bg-indigo-50 px-3 py-1 rounded-full" x-text="fact.category || 'Expérience'"></span>
                        
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="editFact(fact)" class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </button>
                            <button @click="confirmDelete(fact.id)" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div x-show="editingId !== fact.id">
                        <p class="text-sm text-gray-700 leading-relaxed font-medium" x-text="fact.content"></p>
                    </div>

                    <div x-show="editingId === fact.id" class="space-y-3">
                        <textarea x-model="editContent" class="w-full text-sm border-gray-100 bg-gray-50 rounded-2xl focus:ring-indigo-500 focus:border-indigo-500 border-none" rows="4"></textarea>
                        <div class="flex justify-end gap-2">
                            <button @click="editingId = null" class="text-xs font-bold text-gray-400 px-3 py-1.5">Annuler</button>
                            <button @click="updateFact(fact)" class="text-xs font-bold bg-indigo-600 text-white px-4 py-1.5 rounded-xl shadow-lg shadow-indigo-200 hover:bg-indigo-700">Enregistrer</button>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-50 relative">
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="skill in fact.skills" :key="skill.id">
                            <div class="flex items-center gap-1 bg-white border border-gray-100 text-gray-500 px-2 py-0.5 rounded-lg shadow-sm group/skill hover:border-indigo-200 transition-all">
                                <span class="text-[9px] font-bold" x-text="skill.label"></span>
                                <div class="flex items-center gap-0.5 ml-1 opacity-0 group-hover/skill:opacity-100 transition-opacity">
                                    <button @click="detachSkill(fact, skill)" class="p-0.5 hover:text-amber-500 transition-colors" title="Détacher de ce récit">
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                    <button @click="confirmBlacklist(skill)" class="p-0.5 hover:text-red-500 transition-colors" title="Blacklister globalement">
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <template x-if="!fact.skills || fact.skills.length === 0">
                            <span class="text-[9px] text-gray-300 italic">Aucune compétence liée</span>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="facts.length === 0">
            <div class="col-span-full py-20 text-center bg-gray-50 rounded-[3rem] border-2 border-dashed border-gray-200">
                <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                </div>
                <p class="text-gray-400 font-medium">Commencez par discuter avec l'assistant pour extraire vos premiers récits.</p>
                <a href="{{ route('profile.builder') }}" class="mt-4 inline-flex items-center px-6 py-2 bg-indigo-600 text-white rounded-xl font-bold text-sm hover:bg-indigo-700 transition-colors">
                    Lancer l'Assistant
                </a>
            </div>
        </template>
    </div>

    <script>
        function manageFacts() {
            return {
                facts: @json($user->facts()->with('skills')->withCount('skills')->orderBy('skills_count', 'desc')->get()),
                editingId: null,
                editContent: '',

                editFact(fact) {
                    this.editingId = fact.id;
                    this.editContent = fact.content;
                },

                notify(message, type = 'success') {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message, type } }));
                },

                async confirmDelete(id) {
                    window.dispatchEvent(new CustomEvent('confirm', { 
                        detail: { 
                            title: 'Supprimer ce récit ?', 
                            message: 'Cette action est irréversible et retirera les compétences associées.',
                            callback: () => this.deleteFact(id)
                        } 
                    }));
                },

                async confirmBlacklist(skill) {
                    window.dispatchEvent(new CustomEvent('confirm', { 
                        detail: { 
                            title: `Blacklister '${skill.label}' ?`, 
                            message: 'Elle sera retirée de partout et ne sera plus jamais suggérée par l\'IA.',
                            callback: () => this.blacklistSkill(skill)
                        } 
                    }));
                },

                async updateFact(fact) {
                    try {
                        const response = await fetch(`/profile/builder/facts/${fact.id}`, {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                            body: JSON.stringify({ content: this.editContent })
                        });
                        if (response.ok) {
                            fact.content = this.editContent;
                            this.editingId = null;
                            this.notify('Récit mis à jour');
                        }
                    } catch (e) { this.notify('Erreur lors de la mise à jour', 'error'); }
                },

                async deleteFact(id) {
                    try {
                        const response = await fetch(`/profile/builder/facts/${id}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                        });
                        if (response.ok) {
                            this.facts = this.facts.filter(f => f.id !== id);
                            this.notify('Récit supprimé');
                        }
                    } catch (e) { this.notify('Erreur lors de la suppression', 'error'); }
                },

                async detachSkill(fact, skill) {
                    try {
                        const response = await fetch(`/profile/facts/${fact.id}/skills/${skill.id}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                        });
                        if (response.ok) {
                            fact.skills = fact.skills.filter(s => s.id !== skill.id);
                            this.notify('Compétence détachée');
                        }
                    } catch (e) { this.notify('Erreur lors du détachement', 'error'); }
                },

                async blacklistSkill(skill) {
                    try {
                        const response = await fetch(`/profile/skills/${skill.id}/blacklist`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                        });
                        if (response.ok) {
                            this.facts.forEach(f => {
                                f.skills = f.skills.filter(s => s.id !== skill.id);
                            });
                            this.notify(`'${skill.label}' a été blacklistée`);
                        }
                    } catch (e) { this.notify('Erreur lors du blacklistage', 'error'); }
                }
            }
        }
    </script>
</section>
