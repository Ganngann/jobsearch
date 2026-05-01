<x-app-layout>
    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        @keyframes pulse-indigo {
            0% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(79, 70, 229, 0); }
            100% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0); }
        }
        .animate-pulse-update {
            animation: pulse-indigo 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>

    <div class="py-6 bg-gray-50/50" x-data="profileBuilder()" x-cloak>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-row gap-4 lg:gap-6 items-start">
                
                <!-- 1. LEFT SIDEBAR: HISTORY -->
                <aside class="w-48 lg:w-56 flex-shrink-0 hidden md:flex flex-col" style="max-height: 80vh;">
                    <div class="bg-white border border-gray-100 shadow-sm rounded-2xl flex flex-col h-full overflow-hidden">
                        <div class="p-3 border-b border-gray-50 flex items-center justify-between bg-gray-50/30">
                            <h2 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Historique</h2>
                            <a href="{{ route('profile.builder.reset') }}" class="p-1 text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Nouvelle discussion">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </a>
                        </div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1">
                            <template x-for="session in sessions" :key="session.session_id">
                                <a :href="'?session=' + session.session_id" 
                                   class="group block p-2.5 rounded-xl transition-all border"
                                   :class="session.session_id === currentSessionId ? 'bg-indigo-50 border-indigo-100 text-indigo-700' : 'bg-transparent border-transparent hover:bg-gray-50 text-gray-500'">
                                    <div class="text-xs font-medium leading-snug" 
                                         style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;"
                                         x-text="session.title || 'Nouvelle discussion'"></div>
                                    <div class="text-[9px] mt-2 opacity-50" x-text="new Date(session.created_at).toLocaleDateString()"></div>
                                </a>
                            </template>
                        </div>
                    </div>
                </aside>

                <!-- 2. CENTER: MAIN CHAT -->
                <main class="flex-1 min-w-0 flex flex-col overflow-hidden min-h-0">
                    <div class="bg-white border border-gray-100 shadow-sm rounded-2xl flex flex-col overflow-hidden min-h-0">
                        <!-- Header Chat -->
                        <div class="p-3 border-b border-gray-100 flex items-center justify-between bg-gray-50/30 shrink-0">
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></div>
                                <h1 class="text-xs font-semibold text-gray-600">Assistant Coach</h1>
                            </div>
                        </div>

                        <!-- Messages Area -->
                        <div class="flex-1 overflow-y-auto p-4 lg:p-6 space-y-6 custom-scrollbar bg-white min-h-0" 
                             style="max-height: 70vh;" 
                             id="chat-messages">
                            <template x-if="messages.length === 0">
                                <div class="h-full flex flex-col items-center justify-center opacity-30 select-none text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-indigo-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    <p class="text-xs font-medium">Parlez-moi de votre parcours...</p>
                                </div>
                            </template>

                            <template x-for="msg in messages" :key="msg.id">
                                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                                    <div :class="msg.role === 'user' ? 'bg-indigo-600 text-white shadow-indigo-100' : 'bg-gray-100 text-gray-800 border border-gray-200/50'" 
                                         class="max-w-[85%] rounded-2xl px-4 py-3 shadow-sm text-sm leading-relaxed">
                                        <p class="whitespace-pre-wrap" x-text="msg.content"></p>
                                    </div>
                                </div>
                            </template>

                            <!-- Typing Indicator -->
                            <div x-show="isTyping" class="flex justify-start">
                                <div class="bg-gray-100 rounded-2xl px-4 py-2 flex items-center space-x-1">
                                    <div class="w-1 h-1 bg-indigo-400 rounded-full animate-bounce [animation-delay:-0.3s]"></div>
                                    <div class="w-1 h-1 bg-indigo-400 rounded-full animate-bounce [animation-delay:-0.15s]"></div>
                                    <div class="w-1 h-1 bg-indigo-400 rounded-full animate-bounce"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Input Area -->
                        <div class="p-4 border-t border-gray-100 bg-gray-50/30 shrink-0">
                            <form @submit.prevent="sendMessage" class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl p-1 shadow-sm focus-within:ring-2 focus-within:ring-indigo-500/20 focus-within:border-indigo-500 transition-all">
                                <input type="text" 
                                       x-model="newMessage" 
                                       placeholder="Répondez ici..." 
                                       class="flex-1 border-none focus:ring-0 text-sm py-2 px-3 bg-transparent"
                                       :disabled="isTyping">
                                <button type="submit" 
                                        class="bg-indigo-600 text-white p-2 rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 flex items-center justify-center shrink-0"
                                        :disabled="isTyping || !newMessage.trim()">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </main>

                <!-- 3. RIGHT SIDEBAR: FACTS (PROFILE) -->
                <aside class="w-64 lg:w-72 flex-shrink-0 hidden md:flex flex-col" style="max-height: 80vh;">
                    <div class="bg-white border border-gray-100 shadow-sm rounded-2xl flex flex-col h-full overflow-hidden">
                        <div class="p-3 border-b border-gray-50 bg-gray-50/30 flex items-center justify-between">
                            <h2 class="text-xs font-bold text-gray-800 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Mon Profil
                            </h2>
                            <div class="flex bg-gray-200/50 p-0.5 rounded-lg">
                                <button @click="showAllFacts = false" 
                                        :class="!showAllFacts ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-500'"
                                        class="px-2 py-0.5 text-[9px] font-bold rounded-md transition-all uppercase tracking-tight">Session</button>
                                <button @click="showAllFacts = true" 
                                        :class="showAllFacts ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-500'"
                                        class="px-2 py-0.5 text-[9px] font-bold rounded-md transition-all uppercase tracking-tight ml-0.5">Tout</button>
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar p-3 space-y-3 bg-gray-50/10">
                            <template x-for="fact in filteredFacts" :key="fact.id">
                                <div class="bg-white p-3 rounded-xl border border-gray-100 shadow-sm transition-all hover:shadow-md group relative"
                                     x-data="{ confirmingDelete: false }"
                                     :class="{ 
                                        'border-indigo-100 ring-2 ring-indigo-50': fact.status === 'draft',
                                        'animate-pulse-update': fact.justUpdated 
                                     }">
                                    
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[8px] px-1.5 py-0.5 rounded-full uppercase font-bold tracking-widest"
                                                  :class="fact.status === 'draft' ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-500'"
                                                  x-text="fact.category"></span>
                                        </div>
                                        
                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button @click="validateFact(fact)" x-show="fact.status === 'draft' && !confirmingDelete"
                                                    class="p-0.5 text-green-500 hover:bg-green-50 rounded transition" title="Valider">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                            
                                            <!-- Inline Delete Confirmation -->
                                            <div x-show="confirmingDelete" class="flex items-center gap-1">
                                                <button @click="deleteFact(fact)" class="text-[9px] text-red-600 font-bold bg-red-50 px-1.5 py-0.5 rounded">Sûr ?</button>
                                                <button @click="confirmingDelete = false" class="text-[9px] text-gray-400">Non</button>
                                            </div>
                                            
                                            <button @click="confirmingDelete = true" x-show="!confirmingDelete"
                                                    class="p-0.5 text-red-400 hover:bg-red-50 rounded transition" title="Supprimer">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <div x-data="{ editing: false, content: fact.content }">
                                        <div x-show="!editing && !fact.proposed_action" @click="editing = true; content = fact.content" class="cursor-pointer group/text">
                                            <p class="text-xs text-gray-700 leading-relaxed" x-text="fact.content"></p>
                                        </div>

                                        <!-- PROPOSAL UPDATE VIEW -->
                                        <template x-if="fact.proposed_action === 'update'">
                                            <div class="space-y-3">
                                                <div class="opacity-40">
                                                    <p class="text-[8px] font-bold text-gray-400 uppercase mb-1">Actuel :</p>
                                                    <p class="text-[11px] text-gray-600" x-text="fact.content"></p>
                                                </div>
                                                <div class="p-2.5 bg-indigo-50/50 rounded-xl border border-indigo-100 shadow-sm">
                                                    <div class="flex items-center justify-between mb-1">
                                                        <p class="text-[9px] font-bold text-indigo-500 uppercase tracking-tight">Suggestion IA :</p>
                                                        <span class="text-[8px] px-1 bg-indigo-100 text-indigo-600 rounded" x-text="fact.proposed_category"></span>
                                                    </div>
                                                    <p class="text-xs text-indigo-900 leading-relaxed font-medium" x-text="fact.proposed_content"></p>
                                                    <div class="mt-3 flex justify-end gap-2">
                                                        <button @click="rejectProposal(fact)" 
                                                                class="text-[10px] px-2 py-1 bg-white border border-gray-200 text-gray-500 rounded-lg hover:bg-gray-50 transition shadow-sm">Garder l'ancien</button>
                                                        <button @click="acceptProposal(fact)" 
                                                                class="text-[10px] px-2 py-1 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition shadow-md font-bold">Accepter</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- PROPOSAL DELETE VIEW -->
                                        <template x-if="fact.proposed_action === 'delete'">
                                            <div class="p-3 bg-red-50 border border-red-100 rounded-xl">
                                                <div class="flex items-center gap-2 mb-2 text-red-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    <p class="text-[10px] font-bold uppercase tracking-tight">L'IA suggère de supprimer ce fait</p>
                                                </div>
                                                <p class="text-xs text-red-800 line-clamp-3 mb-3 italic" x-text="fact.content"></p>
                                                <div class="flex justify-end gap-2">
                                                    <button @click="rejectProposal(fact)" 
                                                            class="text-[10px] px-2 py-1 bg-white border border-red-200 text-red-600 rounded-lg hover:bg-red-100 transition shadow-sm font-medium">Conserver</button>
                                                    <button @click="acceptProposal(fact)" 
                                                            class="text-[10px] px-2 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700 transition shadow-md font-bold">Confirmer suppression</button>
                                                </div>
                                            </div>
                                        </template>

                                        <div x-show="editing" @click.away="editing = false" class="mt-2">
                                            <textarea x-model="content" 
                                                      class="w-full text-[11px] border-gray-200 rounded-lg p-2 bg-gray-50"
                                                      rows="2"
                                                      @keydown.enter.prevent="saveFact(fact, content); editing = false"></textarea>
                                            <div class="mt-1 flex justify-end gap-1.5">
                                                <button @click="editing = false" class="text-[9px] text-gray-500 font-medium">Annuler</button>
                                                <button @click="saveFact(fact, content); editing = false" 
                                                        class="text-[9px] bg-indigo-600 text-white px-2 py-0.5 rounded font-medium">OK</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </aside>

            </div>
        </div>
    </div>

    <script>
        function profileBuilder() {
            return {
                messages: @json($messages),
                facts: @json($facts),
                sessions: @json($sessions),
                currentSessionId: @json($sessionId),
                newMessage: '',
                isTyping: false,
                showAllFacts: false,

                get filteredFacts() {
                    if (this.showAllFacts) return this.facts;
                    return this.facts.filter(f => f.session_id === this.currentSessionId || f.proposed_content);
                },

                init() {
                    this.scrollToBottom();
                },

                scrollToBottom() {
                    setTimeout(() => {
                        const el = document.getElementById('chat-messages');
                        if (el) el.scrollTop = el.scrollHeight;
                    }, 100);
                },

                async sendMessage() {
                    const message = this.newMessage.trim();
                    if (!message) return;

                    // On réinitialise les indicateurs de mise à jour du tour précédent
                    this.facts = this.facts.map(f => ({ ...f, justUpdated: false }));

                    this.newMessage = '';
                    this.messages.push({
                        id: Date.now(),
                        role: 'user',
                        content: message
                    });
                    this.scrollToBottom();
                    this.isTyping = true;

                    try {
                        const response = await fetch("{{ route('profile.builder.message') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ message: message })
                        });

                        const data = await response.json();
                        this.messages.push({
                            id: Date.now(),
                            role: 'assistant',
                            content: data.reply
                        });

                        // On marque les nouveaux faits ou les faits modifiés pour l'animation
                        const oldFactIds = this.facts.map(f => f.id);
                        const newFacts = data.facts.map(f => {
                            const isNew = !oldFactIds.includes(f.id);
                            const oldFact = this.facts.find(of => of.id === f.id);
                            const isChanged = oldFact && oldFact.content !== f.content;
                            
                            return { ...f, justUpdated: isNew || isChanged };
                        });

                        this.facts = newFacts;
                        this.sessions = data.sessions;
                        this.scrollToBottom();
                    } catch (error) {
                        console.error('Error:', error);
                    } finally {
                        this.isTyping = false;
                    }
                },

                async validateFact(fact) {
                    try {
                        await fetch(`/profile/builder/facts/${fact.id}/validate`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        fact.status = 'validated';
                        fact.proposed_content = null;
                    } catch (error) { console.error(error); }
                },

                async acceptProposal(fact) {
                    try {
                        const response = await fetch(`/profile/builder/facts/${fact.id}/accept`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            if (data.deleted) {
                                this.facts = this.facts.filter(f => f.id !== fact.id);
                            } else {
                                fact.content = fact.proposed_content;
                                fact.category = fact.proposed_category || fact.category;
                                fact.proposed_content = null;
                                fact.proposed_category = null;
                                fact.proposed_action = null;
                                fact.status = 'validated';
                            }
                        }
                    } catch (error) { console.error(error); }
                },

                async rejectProposal(fact) {
                    try {
                        await fetch(`/profile/builder/facts/${fact.id}/reject`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        fact.proposed_content = null;
                        fact.proposed_category = null;
                    } catch (error) { console.error(error); }
                },

                async saveFact(fact, newContent) {
                    try {
                        await fetch(`/profile/builder/facts/${fact.id}`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ content: newContent })
                        });
                        fact.content = newContent;
                        fact.status = 'validated';
                    } catch (error) { console.error(error); }
                },

                async deleteFact(fact) {
                    try {
                        await fetch(`/profile/builder/facts/${fact.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        this.facts = this.facts.filter(f => f.id !== fact.id);
                    } catch (error) { console.error('Delete error:', error); }
                }
            }
        }
    </script>
</x-app-layout>
