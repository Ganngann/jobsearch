<section class="mt-4">
    <header class="mb-8">
        <h2 class="text-2xl font-black text-gray-900 tracking-tight">
            {{ __('Mes Récits & Expériences') }}
        </h2>
        <p class="mt-2 text-sm text-gray-500 max-w-2xl">
            {{ __("Ces faits constituent la base de votre profil et sont utilisés par l'IA pour comprendre votre parcours et vous suggérer des opportunités pertinentes.") }}
        </p>
    </header>

    <div x-data="manageFacts({
        facts: {{ Js::from($user->facts()->orderBy('updated_at', 'desc')->get()) }},
        csrfToken: {{ Js::from(csrf_token()) }}
    })" class="grid grid-cols-1 md:grid-cols-6 gap-4">
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


</section>
