<x-app-layout>
    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        /* CV Preview Styling */
        .cv-paper {
            background-color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border-radius: 4px;
            padding: 40px;
            font-family: 'Inter', sans-serif;
            min-height: 297mm; /* A4 Ratio */
            width: 100%;
            margin: 0 auto;
            color: #1f2937;
        }
        .cv-header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .cv-section-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: #4f46e5;
            margin-top: 25px;
            margin-bottom: 15px;
            display: flex;
            items-center;
            gap: 10px;
        }
        .cv-section-title::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }
        .cv-item {
            margin-bottom: 18px;
        }
        .cv-date {
            font-size: 10px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            width: 80px;
            flex-shrink: 0;
        }
        .cv-content {
            flex: 1;
        }
        .cv-item-draft {
            outline: 2px dashed #fbbf24;
            outline-offset: 4px;
            background-color: #fffbeb;
            padding: 10px;
            border-radius: 4px;
        }
        .cv-item-new {
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse-indigo {
            0% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(79, 70, 229, 0); }
            100% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0); }
        }
        .animate-pulse-update {
            animation: pulse-indigo 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        .diff-added {
            background-color: #dcfce7;
            color: #166534;
            padding: 0 2px;
            border-radius: 2px;
            font-weight: 600;
        }
        .diff-deleted {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 0 2px;
            border-radius: 2px;
            text-decoration: line-through;
            opacity: 0.7;
        }
    </style>

    <div class="h-[calc(100vh-64px)] bg-gray-50/30 overflow-hidden" x-data="profileBuilder()" x-cloak>
        <div class="h-full flex flex-row">
            
            <!-- 1. LEFT SIDEBAR: CONVERSATIONS (15%) -->
            <aside class="w-56 border-r border-gray-200 bg-white flex flex-col hidden lg:flex">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-[11px] font-extrabold text-gray-400 uppercase tracking-widest">Discussions</h2>
                    <a href="{{ route('profile.builder.reset') }}" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Nouvelle discussion">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </a>
                </div>
                
                <div class="flex-1 overflow-y-auto custom-scrollbar p-3 space-y-2">
                    <template x-for="session in activeSessions" :key="session.id">
                        <div class="group relative">
                            <a :href="'?session=' + session.id" 
                               class="block p-3 rounded-xl transition-all border"
                               :class="session.id === currentSessionId ? 'bg-indigo-50 border-indigo-100 text-indigo-700' : 'bg-transparent border-transparent hover:bg-gray-50 text-gray-500'">
                                <div class="text-[11px] font-semibold leading-tight line-clamp-2" x-text="session.title || 'Nouvelle discussion'"></div>
                                <div class="text-[9px] mt-1 opacity-50" x-text="new Date(session.created_at).toLocaleDateString()"></div>
                            </a>
                        </div>
                    </template>
                </div>
            </aside>

            <!-- 2. CENTER: CHAT FLOW (35%) -->
            <main class="w-[450px] border-r border-gray-100 flex flex-col bg-white relative shrink-0">
                <div class="flex-1 flex flex-col w-full overflow-hidden">
                    
                    <!-- Chat Header -->
                    <div class="flex items-center justify-between mb-4 px-2">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white shadow-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-sm font-bold text-gray-800">Coach Narratif</h1>
                                <p class="text-[10px] text-green-500 font-bold flex items-center gap-1">
                                    <span class="w-1 h-1 bg-green-500 rounded-full animate-ping"></span> En ligne
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <div class="flex-1 overflow-y-auto custom-scrollbar space-y-4 pb-28 px-4" id="chat-messages">
                        <template x-for="(msg, index) in messages" :key="msg.id">
                            <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                                <div :class="msg.role === 'user' ? 'bg-indigo-600 text-white shadow-indigo-100' : 'bg-gray-50 text-gray-800 border border-gray-100'" 
                                     class="max-w-[90%] rounded-2xl px-4 py-3 text-[12px] leading-relaxed relative">
                                    <p class="whitespace-pre-wrap" x-text="msg.content"></p>
                                </div>
                            </div>
                        </template>

                        <!-- Typing Indicator -->
                        <div x-show="isTyping" class="flex justify-start pl-4">
                            <div class="flex gap-1.5 p-3 bg-gray-100 rounded-2xl">
                                <div class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0s"></div>
                                <div class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                                <div class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Input Area -->
                    <div class="p-4 border-t border-gray-100 bg-white absolute bottom-0 left-0 right-0 z-10">
                        <div class="relative flex items-end gap-2 bg-gray-50 rounded-2xl p-2 border border-gray-100 focus-within:border-indigo-300 focus-within:ring-2 focus-within:ring-indigo-100 transition-all">
                            <textarea 
                                x-model="newMessage" 
                                x-ref="messageInput"
                                @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); sendMessage(); }"
                                placeholder="Tapez votre message ici..." 
                                class="flex-1 bg-transparent border-none focus:ring-0 text-sm py-2 px-3 custom-scrollbar resize-none max-h-32"
                                rows="1"
                            ></textarea>
                            <button 
                                @click="sendMessage()"
                                :disabled="!newMessage.trim() || isTyping"
                                class="p-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-lg shadow-indigo-100"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </main>

            <!-- 3. RIGHT SIDEBAR: LIVE CV PREVIEW (50%) -->
            <aside class="flex-1 bg-gray-100/50 flex flex-col overflow-hidden">
                <div class="p-3 border-b border-gray-200 bg-white flex items-center justify-between sticky top-0 z-20">
                    <div class="flex items-center gap-3">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Aperçu du CV</span>
                        <div class="h-4 w-px bg-gray-200"></div>
                        <div class="flex items-center gap-2">
                            <div class="w-24 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                <div class="bg-indigo-600 h-full transition-all duration-1000" style="width: {{ $stats['depth_percentage'] }}%"></div>
                            </div>
                            <span class="text-[10px] font-black text-indigo-600">{{ $stats['depth_percentage'] }}%</span>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-8 lg:p-12 custom-scrollbar">
                    <div class="cv-paper">
                        
                        <!-- CV HEADER -->
                        <div class="cv-header">
                            <template x-if="editingItem.type !== 'user'">
                                <div @dblclick="startEditingUser()" class="flex justify-between items-end cursor-pointer group relative">
                                    <div>
                                        <h1 class="text-2xl font-black text-gray-900 tracking-tight leading-none" x-text="user.name"></h1>
                                        <p class="text-indigo-600 font-bold text-[11px] uppercase tracking-[0.2em] mt-3">Candidat à l'emploi</p>
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
                                    <div class="absolute -right-2 top-0 opacity-0 group-hover:opacity-100 transition-opacity p-1 bg-gray-100 rounded text-[8px] text-gray-400">Éditer profil</div>
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
                                    
                                    <!-- Link Repeater -->
                                    <div class="space-y-2">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Liens (GitHub, LinkedIn, etc.)</p>
                                        <template x-for="(link, index) in editingData.links" :key="index">
                                            <div class="flex gap-2">
                                                <input type="text" x-model="link.label" class="w-24 text-[10px] border-gray-200 rounded" placeholder="Label">
                                                <input type="text" x-model="link.url" class="flex-1 text-[10px] border-gray-200 rounded" placeholder="URL">
                                                <button @click="removeLink(index)" class="text-red-400 hover:text-red-600">
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
                                        <button @click="editingItem = {type:null}" class="text-xs text-gray-400">Annuler</button>
                                        <button @click="saveUserEdit()" class="text-xs bg-indigo-600 text-white px-4 py-1.5 rounded font-bold transition hover:bg-indigo-700">Enregistrer</button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- SECTION SKILLS -->
                        <div class="mt-6 flex flex-wrap gap-2 mb-8">
                            <template x-for="skill in skills" :key="skill.id">
                                <div class="relative group">
                                    <template x-if="editingItem.id !== skill.id || editingItem.type !== 'skill'">
                                        <div class="relative">
                                            <span @dblclick="startEditing('skill', skill)" 
                                                  class="text-[9px] font-bold bg-gray-900 text-white px-3 py-1 rounded tracking-wider uppercase cursor-pointer hover:bg-indigo-600 transition-colors"
                                                  x-text="skill.name"></span>
                                            <button @click="deleteItem('skill', skill.id)" 
                                                    class="absolute -top-1.5 -right-1.5 bg-red-500 text-white rounded-full w-3.5 h-3.5 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600 shadow-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="editingItem.id === skill.id && editingItem.type === 'skill'">
                                        <input type="text" x-model="editingData.name" @keyup.enter="saveManualEdit()" @blur="saveManualEdit()"
                                               class="text-[9px] font-bold uppercase bg-white border-indigo-600 px-2 py-1 rounded w-24">
                                    </template>
                                </div>
                            </template>
                        </div>

                        <!-- SECTION EXPÉRIENCES -->
                        <div class="cv-section-title">Expériences Professionnelles</div>
                        <div class="space-y-6">
                            <template x-for="exp in all_experiences" :key="exp.id">
                                <div class="cv-item flex gap-6" :class="exp.status === 'draft' ? 'cv-item-draft' : ''">
                                    <div class="cv-date">
                                        <span x-text="exp.start_date ? new Date(exp.start_date).getFullYear() : '?'"></span>
                                        — 
                                        <span x-text="exp.is_current ? 'Présent' : (exp.end_date ? new Date(exp.end_date).getFullYear() : '?')"></span>
                                    </div>
                                    <div class="cv-content">
                                        <template x-if="editingItem.id !== exp.id || editingItem.type !== 'experience'">
                                            <div @dblclick="startEditing('experience', exp)" class="cursor-pointer group relative">
                                                    <div class="flex items-center gap-2">
                                                        <h3 class="text-[12px] font-bold text-gray-900">
                                                            <template x-if="exp.proposed_action === 'update' && exp.proposed_data && exp.proposed_data.title">
                                                                <span x-html="renderDiff(exp.title, exp.proposed_data.title)"></span>
                                                            </template>
                                                            <template x-if="!(exp.proposed_action === 'update' && exp.proposed_data && exp.proposed_data.title)">
                                                                <span x-text="exp.title"></span>
                                                            </template>
                                                        </h3>
                                                        <button @click.stop="deleteItem('experience', exp.id)" class="opacity-0 group-hover:opacity-100 transition-opacity text-red-400 hover:text-red-600">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                    <span class="text-[11px] font-semibold text-gray-500">
                                                        <template x-if="exp.proposed_action === 'update' && exp.proposed_data && exp.proposed_data.company">
                                                            <span x-html="renderDiff(exp.company, exp.proposed_data.company)"></span>
                                                        </template>
                                                        <template x-if="!(exp.proposed_action === 'update' && exp.proposed_data && exp.proposed_data.company)">
                                                            <span x-text="exp.company"></span>
                                                        </template>
                                                    </span>
                                                </div>
                                                <p class="text-[11px] text-gray-600 mt-1 leading-relaxed">
                                                    <template x-if="exp.proposed_action === 'update' && exp.proposed_data && exp.proposed_data.description">
                                                        <span x-html="renderDiff(exp.description, exp.proposed_data.description)"></span>
                                                    </template>
                                                    <template x-if="!(exp.proposed_action === 'update' && exp.proposed_data && exp.proposed_data.description)">
                                                        <span x-text="exp.description"></span>
                                                    </template>
                                                </p>
                                                
                                                <template x-if="exp.proposed_action">
                                                    <div class="mt-2 flex gap-2">
                                                        <button @click.stop="acceptItem('experience', exp.id)" class="text-[9px] px-2 py-1 bg-indigo-600 text-white rounded font-bold">Accepter</button>
                                                        <button @click.stop="rejectItem('experience', exp.id)" class="text-[9px] px-2 py-1 bg-white border border-gray-200 text-gray-500 rounded font-bold">Refuser</button>
                                                    </div>
                                                </template>

                                                <div class="mt-1 opacity-0 group-hover:opacity-100 transition-opacity text-[8px] text-gray-400">Double-clic pour éditer</div>
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
                                                <textarea x-model="editingData.description" class="w-full text-[11px] border-gray-200 rounded p-1" rows="3" placeholder="Description"></textarea>
                                                <div class="flex justify-end gap-2">
                                                    <button @click="deleteItem('experience', exp.id)" class="text-[10px] text-red-400 mr-auto hover:text-red-600">Supprimer</button>
                                                    <button @click="editingItem = {id:null}" class="text-[10px] text-gray-400">Annuler</button>
                                                    <button @click="saveManualEdit()" class="text-[10px] bg-indigo-600 text-white px-3 py-1 rounded font-bold">Enregistrer</button>
                                                </div>
                                            </div>
                                        </template>
                                        
                                        <template x-if="exp.status === 'draft' && editingItem.id !== exp.id">
                                            <div class="mt-3 flex gap-2">
                                                <button @click="acceptItem('experience', exp.id)" class="text-[9px] px-3 py-1 bg-indigo-600 text-white rounded font-bold hover:bg-indigo-700 shadow-sm transition-all">Valider</button>
                                                <button @click="deleteItem('experience', exp.id)" class="text-[9px] px-3 py-1 bg-white border border-gray-200 text-gray-500 rounded hover:bg-gray-50">Supprimer</button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- SECTION FORMATIONS -->
                        <div class="cv-section-title">Formations</div>
                        <div class="space-y-4">
                            <template x-for="edu in all_educations" :key="edu.id">
                                <div class="cv-item flex gap-6" :class="edu.status === 'draft' ? 'cv-item-draft' : ''">
                                    <div class="cv-date" x-text="edu.graduation_year"></div>
                                    <div class="cv-content">
                                        <template x-if="editingItem.id !== edu.id || editingItem.type !== 'education'">
                                            <div @dblclick="startEditing('education', edu)" class="cursor-pointer group relative">
                                                <div class="flex justify-between items-baseline">
                                                    <div class="flex items-center gap-2">
                                                        <h3 class="text-[12px] font-bold text-gray-900">
                                                            <template x-if="edu.proposed_action === 'update' && edu.proposed_data && edu.proposed_data.degree">
                                                                <span x-html="renderDiff(edu.degree, edu.proposed_data.degree)"></span>
                                                            </template>
                                                            <template x-if="!(edu.proposed_action === 'update' && edu.proposed_data && edu.proposed_data.degree)">
                                                                <span x-text="edu.degree"></span>
                                                            </template>
                                                        </h3>
                                                        <button @click.stop="deleteItem('education', edu.id)" class="opacity-0 group-hover:opacity-100 transition-opacity text-red-400 hover:text-red-600">
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
                                                
                                                <div class="mt-1">
                                                    <template x-if="edu.proposed_action === 'update' && edu.proposed_data && edu.proposed_data.description">
                                                        <div class="text-[11px] text-gray-600 leading-relaxed" x-html="renderDiff(edu.description, edu.proposed_data.description)"></div>
                                                    </template>
                                                    <template x-if="!(edu.proposed_action === 'update' && edu.proposed_data && edu.proposed_data.description)">
                                                        <div class="text-[11px] text-gray-600 leading-relaxed" x-text="edu.description"></div>
                                                    </template>
                                                </div>
                                                
                                                <template x-if="edu.proposed_action">
                                                    <div class="mt-2 flex gap-2">
                                                        <button @click.stop="acceptItem('education', edu.id)" class="text-[9px] px-2 py-1 bg-indigo-600 text-white rounded font-bold">Accepter</button>
                                                        <button @click.stop="rejectItem('education', edu.id)" class="text-[9px] px-2 py-1 bg-white border border-gray-200 text-gray-500 rounded font-bold">Refuser</button>
                                                    </div>
                                                </template>

                                                <div class="mt-1 opacity-0 group-hover:opacity-100 transition-opacity text-[8px] text-gray-400">Double-clic pour éditer</div>
                                            </div>
                                        </template>

                                        <template x-if="editingItem.id === edu.id && editingItem.type === 'education'">
                                            <div class="space-y-2 bg-gray-50 p-3 rounded-lg border border-indigo-100">
                                                <input type="text" x-model="editingData.degree" class="w-full text-[11px] font-bold border-gray-200 rounded p-1" placeholder="Diplôme">
                                                <div class="flex gap-2">
                                                    <input type="text" x-model="editingData.school" class="flex-1 text-[10px] border-gray-200 rounded p-1" placeholder="École">
                                                    <input type="number" x-model="editingData.graduation_year" class="w-20 text-[10px] border-gray-200 rounded p-1" placeholder="Année">
                                                </div>
                                                <div class="flex justify-end gap-2">
                                                    <button @click="editingItem = {id:null}" class="text-[9px] text-gray-400">Annuler</button>
                                                    <button @click="saveManualEdit()" class="text-[9px] bg-indigo-600 text-white px-2 py-0.5 rounded font-bold">OK</button>
                                                </div>
                                            </div>
                                        </template>

                                        <template x-if="edu.status === 'draft' && editingItem.id !== edu.id">
                                            <div class="mt-2 flex gap-2">
                                                <button @click="acceptItem('education', edu.id)" class="text-[9px] px-3 py-1 bg-indigo-600 text-white rounded font-bold">Valider</button>
                                                <button @click="deleteItem('education', edu.id)" class="text-[9px] px-3 py-1 bg-white border border-gray-200 text-gray-500 rounded">Supprimer</button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- SECTION PROJETS & RÉALISATIONS -->
                        <div class="cv-section-title">Réalisations & Projets</div>
                        <div class="grid grid-cols-2 gap-x-8 gap-y-4">
                            <template x-for="project in projects" :key="project.id">
                                <div :class="project.status === 'draft' ? 'cv-item-draft' : ''" class="group relative">
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
                                            <p class="text-[10px] text-gray-500 mt-1">
                                                <template x-if="project.proposed_action === 'update' && project.proposed_data && project.proposed_data.description">
                                                    <span x-html="renderDiff(project.description, project.proposed_data.description)"></span>
                                                </template>
                                                <template x-if="!(project.proposed_action === 'update' && project.proposed_data && project.proposed_data.description)">
                                                    <span x-text="project.description"></span>
                                                </template>
                                            </p>
                                            
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
                                            <div class="flex gap-2">
                                                <input type="date" x-model="editingData.start_date" class="flex-1 text-[9px] border-gray-200 rounded p-1">
                                                <label class="flex items-center gap-1 text-[9px] text-gray-500">
                                                    <input type="checkbox" x-model="editingData.is_ongoing" class="rounded text-indigo-600"> En cours
                                                </label>
                                            </div>
                                            <textarea x-model="editingData.description" class="w-full text-[9px] border-gray-200 rounded p-1" rows="2" placeholder="Description"></textarea>
                                            <div class="flex justify-end gap-1">
                                                <button @click="deleteItem('project', project.id)" class="text-[8px] text-red-400 mr-auto hover:text-red-600">Supprimer</button>
                                                <button @click="editingItem = {id:null}" class="text-[8px] text-gray-400">Annuler</button>
                                                <button @click="saveManualEdit()" class="text-[8px] bg-indigo-600 text-white px-2 py-0.5 rounded">OK</button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <!-- SECTION CERTIFICATIONS -->
                        <div class="cv-section-title">Certifications</div>
                        <div class="space-y-2 mb-6">
                            <template x-for="cert in certifications" :key="cert.id">
                                <div class="relative group" :class="cert.status === 'draft' ? 'cv-item-draft' : ''">
                                    <template x-if="editingItem.id !== cert.id || editingItem.type !== 'certification'">
                                        <div @dblclick="startEditing('certification', cert)" class="flex items-center gap-2 cursor-pointer">
                                            <div class="w-1.5 h-1.5 bg-yellow-400 rounded-full"></div>
                                            <p class="text-[11px] font-bold text-gray-700" x-text="cert.name"></p>
                                        </div>
                                    </template>
                                    <template x-if="editingItem.id === cert.id && editingItem.type === 'certification'">
                                        <div class="flex gap-2">
                                            <input type="text" x-model="editingData.name" class="flex-1 text-[10px] border-gray-200 rounded p-1" placeholder="Nom">
                                            <input type="date" x-model="editingData.issue_date" class="w-32 text-[10px] border-gray-200 rounded p-1">
                                            <button @click="saveManualEdit()" class="text-[10px] text-indigo-600 font-bold">OK</button>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <!-- SECTION BÉNÉVOLAT -->
                        <div class="cv-section-title">Engagement Associatif</div>
                        <div class="space-y-4 mb-6">
                            <template x-for="vol in volunteer_experiences" :key="vol.id">
                                <div class="cv-item" :class="vol.status === 'draft' ? 'cv-item-draft' : ''">
                                    <template x-if="editingItem.id !== vol.id || editingItem.type !== 'volunteer'">
                                        <div @dblclick="startEditing('volunteer', vol)" class="cursor-pointer">
                                            <h4 class="text-[11px] font-bold text-gray-900" x-text="vol.role"></h4>
                                            <p class="text-[10px] text-gray-500" x-text="vol.organization"></p>
                                        </div>
                                    </template>
                                    <template x-if="editingItem.id === vol.id && editingItem.type === 'volunteer'">
                                        <div class="space-y-1">
                                            <input type="text" x-model="editingData.role" class="w-full text-[10px] font-bold border-gray-200 rounded p-1" placeholder="Rôle">
                                            <input type="text" x-model="editingData.organization" class="w-full text-[9px] border-gray-200 rounded p-1" placeholder="Organisation">
                                            <div class="flex gap-2">
                                                <input type="date" x-model="editingData.start_date" class="flex-1 text-[9px] border-gray-200 rounded p-1">
                                                <input type="date" x-model="editingData.end_date" class="flex-1 text-[9px] border-gray-200 rounded p-1">
                                            </div>
                                            <button @click="saveManualEdit()" class="text-[9px] bg-indigo-600 text-white px-2 py-0.5 rounded">OK</button>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <!-- SECTION PERSONNALITÉ (FACTS) -->
                        <div class="cv-section-title">Points Forts & Atouts</div>
                        <div class="grid grid-cols-1 gap-3">
                            <template x-for="fact in filteredFacts" :key="fact.id">
                                <div class="relative group" :class="fact.proposed_action ? 'bg-amber-50 p-3 rounded-lg border-2 border-amber-200' : ''">
                                    <template x-if="editingItem.id !== fact.id || editingItem.type !== 'fact'">
                                        <div @dblclick="startEditing('fact', fact)" class="flex gap-3 items-start cursor-pointer relative group">
                                            <div class="w-1.5 h-1.5 bg-indigo-400 rounded-full mt-1.5 flex-shrink-0" :class="fact.proposed_action ? 'bg-amber-400' : ''"></div>
                                            <div class="flex-1">
                                                <template x-if="fact.proposed_action === 'update'">
                                                    <div class="space-y-1 mb-1">
                                                        <p class="text-[11px] text-amber-900 leading-relaxed inline font-semibold">
                                                            <span class="font-bold text-[9px] uppercase text-amber-500 mr-1" x-text="fact.category"></span>
                                                            <span x-html="renderDiff(fact.content, fact.proposed_content)"></span>
                                                        </p>
                                                    </div>
                                                </template>
                                                <template x-if="fact.proposed_action !== 'update'">
                                                    <p class="text-[11px] text-gray-700 leading-relaxed inline" :class="fact.proposed_action === 'delete' ? 'line-through text-red-400' : (fact.proposed_action === 'add' ? 'text-indigo-700 font-medium' : '')">
                                                        <span class="font-bold text-[9px] uppercase text-gray-400 mr-1" x-text="fact.category"></span>
                                                        <span :class="fact.proposed_action === 'add' ? 'diff-added' : ''" x-text="fact.content"></span>
                                                    </p>
                                                </template>

                                                <!-- Buttons for Proposed Actions -->
                                                <template x-if="fact.proposed_action">
                                                    <div class="flex gap-2 mt-2">
                                                        <button @click.stop="acceptFact(fact.id)" class="text-[9px] bg-indigo-600 text-white px-2 py-1 rounded font-bold hover:bg-indigo-700 transition shadow-sm">Accepter</button>
                                                        <button @click.stop="rejectFact(fact.id)" class="text-[9px] bg-white border border-gray-200 text-gray-500 px-2 py-1 rounded font-bold hover:bg-gray-50 transition shadow-sm">Refuser</button>
                                                    </div>
                                                </template>
                                                
                                                <button x-show="!fact.proposed_action" @click.stop="deleteItem('fact', fact.id)" 
                                                        class="inline-flex items-center ml-2 p-0.5 opacity-0 group-hover:opacity-100 transition-opacity text-red-400 hover:text-red-600 align-middle">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="editingItem.id === fact.id && editingItem.type === 'fact'">
                                        <div class="space-y-2">
                                            <select x-model="editingData.category" class="w-full text-[10px] border-gray-200 rounded p-1">
                                                <option value="VALEURS">VALEURS</option>
                                                <option value="OBJECTIFS">OBJECTIFS</option>
                                                <option value="SOFT_SKILLS">SOFT SKILLS</option>
                                                <option value="PREFERENCES">PRÉFÉRENCES</option>
                                            </select>
                                            <textarea x-model="editingData.content" class="w-full text-[11px] border-gray-200 rounded p-1" rows="2"></textarea>
                                            <div class="flex justify-end gap-2">
                                                <button @click="deleteItem('fact', fact.id)" class="text-[9px] text-red-400 mr-auto hover:text-red-600">Supprimer</button>
                                                <button @click="editingItem = {id:null}" class="text-[9px] text-gray-400">Annuler</button>
                                                <button @click="saveManualEdit()" class="text-[9px] bg-indigo-600 text-white px-3 py-1 rounded">OK</button>
                                            </div>
                                        </div>
                                    </template>

                                </div>
                            </template>
                        </div>

                        <!-- INTERESTS -->
                        <div class="mt-12 flex flex-wrap gap-2 pt-6 border-t border-gray-100">
                            <template x-for="interest in interests" :key="interest.id">
                                <div>
                                    <template x-if="editingItem.id !== interest.id || editingItem.type !== 'interest'">
                                        <span @dblclick="startEditing('interest', interest)"
                                              class="text-[9px] font-bold text-gray-400 uppercase tracking-wider px-3 py-1 bg-gray-50 rounded-full border border-gray-100 cursor-pointer hover:border-indigo-300" 
                                              :class="interest.status === 'draft' ? 'border-amber-200 bg-amber-50' : ''"
                                              x-text="interest.name"></span>
                                    </template>
                                    <template x-if="editingItem.id === interest.id && editingItem.type === 'interest'">
                                        <input type="text" x-model="editingData.name" @keyup.enter="saveManualEdit()" @blur="saveManualEdit()"
                                               class="text-[9px] font-bold uppercase border-indigo-300 rounded-full px-3 py-1 w-24">
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </aside>

        </div>
    </div>

    <script>
        function profileBuilder() {
            return {
                messages: @json($messages) || [],
                facts: @json($facts) || [],
                projects: @json($projects) || [],
                certifications: @json($certifications) || [],
                interests: @json($interests) || [],
                volunteer_experiences: @json($volunteer_experiences) || [],
                all_experiences: @json($all_experiences) || [],
                all_educations: @json($all_educations) || [],
                activeSessions: @json($activeSessions) || [],
                archivedSessions: @json($archivedSessions) || [],
                currentSessionId: @json($sessionId),
                user: @json(Auth::user()),
                newMessage: '',
                isTyping: false,
                isSyncing: false,
                showAllFacts: false,
                showArchives: false,
                editingItem: { type: null, id: null },
                editingData: {},

                get filteredFacts() {
                    if (this.showAllFacts) return this.facts;
                    return this.facts.filter(f => f.status === 'validated' || f.proposed_action || f.session_id === this.currentSessionId);
                },

                init() {
                    this.scrollToBottom();
                    this.$nextTick(() => this.$refs.messageInput.focus());
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

                    this.newMessage = '';
                    this.messages.push({ id: Date.now(), role: 'user', content: message });
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
                        this.messages.push({ id: Date.now(), role: 'assistant', content: data.reply });

                        // Update all data arrays
                        this.facts = data.facts;
                        this.projects = data.projects;
                        this.certifications = data.certifications;
                        this.interests = data.interests;
                        this.volunteer_experiences = data.volunteer_experiences;
                        this.all_experiences = data.all_experiences;
                        this.all_educations = data.all_educations;
                        this.skills = data.skills;
                        this.user = data.user;
                        this.activeSessions = data.activeSessions;
                        this.archivedSessions = data.archivedSessions;
                        
                        this.scrollToBottom();
                    } catch (error) {
                        console.error('Error:', error);
                    } finally {
                        this.isTyping = false;
                        this.$nextTick(() => this.$refs.messageInput.focus());
                    }
                },

                formatDate(dateStr) {
                    if (!dateStr) return '';
                    const date = new Date(dateStr);
                    return date.toLocaleDateString('fr-FR');
                },

                renderDiff(oldText, newText) {
                    if (!oldText) return `<span class="diff-added">${newText || ''}</span>`;
                    if (!newText) return `<span class="diff-deleted">${oldText || ''}</span>`;
                    if (oldText === newText) return oldText;

                    const words1 = (oldText || '').toString().split(/\s+/).filter(w => w.length > 0);
                    const words2 = (newText || '').toString().split(/\s+/).filter(w => w.length > 0);
                    let i = 0, j = 0;
                    let html = '';

                    while (i < words1.length || j < words2.length) {
                        if (i < words1.length && j < words2.length && words1[i] === words2[j]) {
                            html += words1[i] + ' ';
                            i++; j++;
                        } else {
                            // Simple lookahead for matching word in words2
                            let foundIn2 = -1;
                            for (let k = j + 1; k < Math.min(j + 10, words2.length); k++) {
                                if (i < words1.length && words1[i] === words2[k]) {
                                    foundIn2 = k;
                                    break;
                                }
                            }
                            
                            if (foundIn2 !== -1) {
                                // Words in between are additions
                                while (j < foundIn2) {
                                    html += `<span class="diff-added">${words2[j]}</span> `;
                                    j++;
                                }
                            } else if (i < words1.length) {
                                // Current word in 1 is deleted
                                html += `<span class="diff-deleted">${words1[i]}</span> `;
                                i++;
                            } else if (j < words2.length) {
                                // Remaining words in 2 are additions
                                html += `<span class="diff-added">${words2[j]}</span> `;
                                j++;
                            }
                        }
                    }
                    return html;
                },

                async acceptFact(id) {
                    try {
                        const response = await fetch(`/profile/builder/facts/${id}/accept`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                        });
                        const data = await response.json();
                        if (data.success) {
                            const fact = this.facts.find(f => f.id === id);
                            if (fact) {
                                if (fact.proposed_action === 'update') {
                                    fact.content = fact.proposed_content;
                                }
                                if (fact.proposed_action === 'delete') {
                                    this.facts = this.facts.filter(f => f.id !== id);
                                } else {
                                    fact.proposed_action = null;
                                    fact.proposed_content = null;
                                    fact.status = 'validated';
                                }
                            }
                        }
                    } catch (error) { console.error(error); }
                },

                async rejectFact(id) {
                    try {
                        const response = await fetch(`/profile/builder/facts/${id}/reject`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                        });
                        const data = await response.json();
                        if (data.success) {
                             const fact = this.facts.find(f => f.id === id);
                             if (fact && fact.proposed_action === 'add') {
                                 this.facts = this.facts.filter(f => f.id !== id);
                             } else if (fact) {
                                 fact.proposed_action = null;
                                 fact.proposed_content = null;
                             }
                        }
                    } catch (error) { console.error(error); }
                },

                async rejectItem(type, id) {
                    try {
                        const response = await fetch(`/profile/builder/item/${type}/${id}/reject`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                        });
                        if ((await response.json()).success) {
                            let list = this.getItemList(type);
                            const index = list.findIndex(i => i.id === id);
                            if (index !== -1) {
                                if (list[index].proposed_action === 'add') {
                                    this.setItemList(type, list.filter(i => i.id !== id));
                                } else {
                                    list[index].proposed_action = null;
                                    list[index].proposed_data = null;
                                }
                            }
                        }
                    } catch (error) { console.error(error); }
                },

                async acceptItem(type, id) {
                    try {
                        const response = await fetch(`/profile/builder/item/${type}/${id}/accept`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                        });
                        if ((await response.json()).success) {
                            let list = this.getItemList(type);
                            const item = list.find(i => i.id === id);
                            if (item) {
                                if (item.proposed_action === 'update' && item.proposed_data) {
                                    Object.assign(item, item.proposed_data);
                                } else if (item.proposed_action === 'delete') {
                                    this.setItemList(type, list.filter(i => i.id !== id));
                                    return;
                                }
                                item.status = 'validated';
                                item.proposed_action = null;
                                item.proposed_data = null;
                            }
                        }
                    } catch (error) { console.error(error); }
                },

                getItemList(type) {
                    if (type === 'experience') return this.all_experiences;
                    if (type === 'education') return this.all_educations;
                    if (type === 'project') return this.projects;
                    if (type === 'certification') return this.certifications;
                    if (type === 'volunteer') return this.volunteer_experiences;
                    if (type === 'interest') return this.interests;
                    return [];
                },

                setItemList(type, newList) {
                    if (type === 'experience') this.all_experiences = newList;
                    else if (type === 'education') this.all_educations = newList;
                    else if (type === 'project') this.projects = newList;
                    else if (type === 'certification') this.certifications = newList;
                    else if (type === 'volunteer') this.volunteer_experiences = newList;
                    else if (type === 'interest') this.interests = newList;
                },

                async deleteItem(type, id) {
                    if (!confirm('Voulez-vous vraiment supprimer cet élément ?')) return;
                    try {
                        const response = await fetch(`/profile/builder/item/${type}/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.removeLocalItem(type, id);
                        }
                    } catch (error) { console.error(error); }
                },

                refreshLocalItemStatus(type, id, status) {
                    const arrays = {
                        'experience': 'all_experiences',
                        'education': 'all_educations',
                        'project': 'projects',
                        'interest': 'interests',
                        'certification': 'certifications',
                        'volunteer': 'volunteer_experiences',
                        'skill': 'skills',
                        'fact': 'facts'
                    };
                    const arrayName = arrays[type];
                    if (arrayName) {
                        this[arrayName] = this[arrayName].map(item => item.id === id ? { ...item, status } : item);
                    }
                },

                removeLocalItem(type, id) {
                    const arrays = {
                        'experience': 'all_experiences',
                        'education': 'all_educations',
                        'project': 'projects',
                        'interest': 'interests',
                        'certification': 'certifications',
                        'volunteer': 'volunteer_experiences',
                        'skill': 'skills',
                        'fact': 'facts'
                    };
                    const arrayName = arrays[type];
                    if (arrayName) {
                        this[arrayName] = this[arrayName].filter(item => item.id !== id);
                    }
                },

                async toggleArchive(sessionId) {
                    try {
                        const response = await fetch(`/profile/builder/sessions/${sessionId}/archive`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        const data = await response.json();
                        this.activeSessions = data.activeSessions;
                        this.archivedSessions = data.archivedSessions;
                    } catch (error) { console.error(error); }
                },

                startEditing(type, item) {
                    this.editingItem = { type, id: item.id };
                    this.editingData = { ...item };
                },

                startEditingUser() {
                    this.editingItem = { type: 'user', id: this.user.id };
                    this.editingData = { ...this.user, links: [...(this.user.links || [])] };
                },

                addLink() {
                    if (!this.editingData.links) this.editingData.links = [];
                    this.editingData.links.push({ label: '', url: '' });
                },

                removeLink(index) {
                    this.editingData.links.splice(index, 1);
                },

                async saveUserEdit() {
                    try {
                        const response = await fetch(`/profile/builder/item/user/${this.user.id}`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(this.editingData)
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.user = data.item;
                            this.editingItem = { type: null, id: null };
                        }
                    } catch (error) { console.error(error); }
                },

                async saveManualEdit() {
                    const { type, id } = this.editingItem;
                    try {
                        const response = await fetch(`/profile/builder/item/${type}/${id}`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(this.editingData)
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.refreshLocalItem(type, id, data.item);
                            this.editingItem = { type: null, id: null };
                        }
                    } catch (error) { console.error(error); }
                },

                refreshLocalItem(type, id, newItem) {
                    const arrays = {
                        'experience': 'all_experiences',
                        'education': 'all_educations',
                        'project': 'projects',
                        'interest': 'interests',
                        'certification': 'certifications',
                        'volunteer': 'volunteer_experiences',
                        'skill': 'skills',
                        'fact': 'facts'
                    };
                    const arrayName = arrays[type];
                    if (arrayName) {
                        this[arrayName] = this[arrayName].map(item => item.id === id ? newItem : item);
                    }
                },

                async syncSkills() {
                    this.isSyncing = true;
                    try {
                        const response = await fetch("{{ route('profile.builder.sync-skills') }}", {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        const data = await response.json();
                        this.facts = data.facts;
                    } catch (error) { console.error(error); } finally {
                        this.isSyncing = false;
                    }
                },

                async acceptProposal(fact) {
                    this.acceptFact(fact.id);
                },

                async rejectProposal(fact) {
                    this.rejectFact(fact.id);
                }
            }
        }
    </script>
</x-app-layout>
