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
            margin-top: 15px;
            margin-bottom: 10px;
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
            margin-bottom: 12px;
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
            outline: 1px dashed #fbbf24;
            outline-offset: 1px;
            background-color: #fffdf5;
            padding: 2px 6px;
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
        /* Reset browser defaults inside CV */
        .cv-paper h1, .cv-paper h2, .cv-paper h3, .cv-paper p, .cv-paper ul, .cv-paper li {
            margin: 0;
            padding: 0;
        }
    </style>

    <div class="h-[calc(100vh-64px)] bg-gray-50/30 overflow-hidden" 
         x-data="profileBuilder({
            messages: {{ Js::from($messages) }},
            facts: {{ Js::from($facts) }},
            projects: {{ Js::from($projects) }},
            certifications: {{ Js::from($certifications) }},
            interests: {{ Js::from($interests) }},
            volunteer_experiences: {{ Js::from($volunteer_experiences) }},
            all_experiences: {{ Js::from($all_experiences) }},
            all_educations: {{ Js::from($all_educations) }},
            activeSessions: {{ Js::from($activeSessions) }},
            archivedSessions: {{ Js::from($archivedSessions) }},
            currentSessionId: {{ Js::from($sessionId) }},
            user: {{ Js::from(Auth::user()) }},
            skills: {{ Js::from(Auth::user()->skills) }},
            routes: {
                message: '{{ route('profile.builder.message') }}',
                syncSkills: '{{ route('profile.builder.sync-skills') }}'
            }
         })" x-cloak>
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
                        </div>
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
                            <x-cv.header />

                            <!-- SECTION COMPÉTENCES -->
                            <x-cv.skills />

                            <!-- SECTION EXPÉRIENCES -->
                            <x-cv.experience />

                            <!-- SECTION FORMATIONS -->
                            <x-cv.education />

                            <!-- SECTION RÉALISATIONS & PROJETS -->
                            <x-cv.projects />

                            <!-- SECTION CERTIFICATIONS -->
                            <x-cv.certifications />

                            <!-- SECTION ENGAGEMENT -->
                            <x-cv.volunteer />

                            <!-- SECTION POINTS FORTS (FACTS) -->
                            <x-cv.facts />

                            <!-- INTERESTS -->
                            <x-cv.interests />
                        </div>
                    </div>
                </aside>

        </div>
    </div>


</x-app-layout>
