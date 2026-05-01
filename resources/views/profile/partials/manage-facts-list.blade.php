<section class="mt-12 pt-12 border-t border-gray-100">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Mes Récits & Expériences (Faits)') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Voici les informations extraites par l'IA lors de vos échanges. Vous pouvez les affiner ou les supprimer ici.") }}
        </p>
    </header>

    <div class="mt-6 space-y-4" x-data="manageFacts()">
        <template x-for="fact in facts" :key="fact.id">
            <div class="p-4 bg-gray-50/50 border border-gray-100 rounded-2xl group transition-all hover:bg-white hover:shadow-md">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <!-- Mode Affichage -->
                        <div x-show="editingId !== fact.id">
                            <p class="text-xs text-gray-700 leading-relaxed" x-text="fact.content"></p>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="text-[9px] font-bold px-1.5 py-0.5 bg-indigo-50 text-indigo-500 rounded uppercase tracking-wider" x-text="fact.category || 'Général'"></span>
                            </div>
                        </div>

                        <!-- Mode Édition -->
                        <div x-show="editingId === fact.id" class="space-y-3">
                            <textarea x-model="editContent" class="w-full text-xs border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500" rows="3"></textarea>
                            <div class="flex justify-end gap-2">
                                <button @click="editingId = null" class="text-[10px] font-bold text-gray-400 hover:text-gray-600 px-2 py-1">Annuler</button>
                                <button @click="updateFact(fact)" class="text-[10px] font-bold bg-indigo-600 text-white px-3 py-1 rounded-lg shadow-sm hover:bg-indigo-700">Enregistrer</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button @click="editingId = fact.id; editContent = fact.content" class="p-1.5 text-gray-400 hover:text-indigo-600 transition-colors" title="Modifier">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>
                        <button @click="deleteFact(fact.id)" class="p-1.5 text-gray-400 hover:text-red-500 transition-colors" title="Supprimer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="facts.length === 0">
            <div class="py-12 text-center bg-gray-50 rounded-3xl border-2 border-dashed border-gray-100">
                <p class="text-sm text-gray-400 italic">Aucun récit validé pour le moment. Utilisez l'Assistant IA pour en générer.</p>
            </div>
        </template>
    </div>

    <script>
        function manageFacts() {
            return {
                facts: @json($user->facts()->where('status', 'validated')->get()),
                editingId: null,
                editContent: '',

                async updateFact(fact) {
                    try {
                        const response = await fetch(`/profile/builder/facts/${fact.id}`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ content: this.editContent })
                        });
                        if (response.ok) {
                            fact.content = this.editContent;
                            this.editingId = null;
                        }
                    } catch (e) { console.error(e); }
                },

                async deleteFact(id) {
                    if (!confirm('Voulez-vous vraiment supprimer ce récit ?')) return;
                    try {
                        const response = await fetch(`/profile/builder/facts/${id}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                        });
                        if (response.ok) {
                            this.facts = this.facts.filter(f => f.id !== id);
                        }
                    } catch (e) { console.error(e); }
                }
            }
        }
    </script>
</section>
