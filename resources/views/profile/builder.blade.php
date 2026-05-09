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
        @keyframes pulse-amber {
            0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
            100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }
        .animate-pulse-update {
            animation: pulse-amber 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
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

    <div class="h-[calc(100vh-112px)] bg-white overflow-hidden flex justify-center" 
         x-data="profileBuilder({
            messages: {{ Js::from($messages) }},
            facts: {{ Js::from($facts) }},
            projects: {{ Js::from($projects) }},
            certifications: {{ Js::from($certifications) }},
            interests: {{ Js::from($interests) }},
            volunteer_experiences: {{ Js::from($volunteer_experiences) }},
            all_experiences: {{ Js::from($all_experiences) }},
            all_educations: {{ Js::from($all_educations) }},
            languages: {{ Js::from($languages) }},
            allAvailableLanguages: {{ Js::from($allAvailableLanguages) }},
            activeSessions: {{ Js::from($activeSessions) }},
            archivedSessions: {{ Js::from($archivedSessions) }},
            currentSessionId: {{ Js::from($sessionId) }},
            user: {{ Js::from(Auth::user()) }},
            skills: {{ Js::from(Auth::user()->skills) }},
            stats: {{ Js::from($stats) }},
            routes: {
                message: '{{ route('profile.builder.message') }}',
                upload: '{{ route('profile.builder.upload') }}',
            }
         })" x-cloak>
        <div class="h-full flex flex-row w-full max-w-[1600px] border-x border-slate-100 shadow-2xl">
            
            <!-- 1. LEFT SIDEBAR: CONVERSATIONS (15%) -->
            <aside class="w-64 border-r border-slate-100 bg-white flex flex-col hidden lg:flex">
                <div class="p-6 border-b border-slate-50 flex items-center justify-between">
                    <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Historique</h2>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('profile.builder.reset') }}" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all shadow-sm shadow-indigo-50/50" title="Nouvelle discussion">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                            </svg>
                        </a>
                    </div>
                </div>
                
                <div class="flex-1 overflow-y-auto custom-scrollbar p-4 space-y-3">
                    <template x-for="session in activeSessions" :key="session.id">
                        <div class="group relative">
                            <a :href="'?session=' + session.id" 
                               class="block p-4 rounded-2xl transition-all border duration-200"
                               :class="session.id === currentSessionId ? 'bg-indigo-50/50 border-indigo-100 shadow-sm' : 'bg-transparent border-transparent hover:bg-slate-50'">
                                <div class="flex items-start gap-3">
                                    <div :class="session.id === currentSessionId ? 'bg-indigo-600' : 'bg-slate-100 group-hover:bg-white transition-colors'" 
                                         class="mt-1 w-2 h-2 rounded-full shrink-0 shadow-sm"></div>
                                    <div class="flex-1 min-w-0">
                                        <p :class="session.id === currentSessionId ? 'text-indigo-900 font-black' : 'text-slate-600 font-bold'"
                                           class="text-[11px] leading-tight line-clamp-2" x-text="session.title || 'Nouvelle discussion'"></p>
                                        <p class="text-[9px] text-slate-400 font-medium mt-1 uppercase tracking-tighter" x-text="new Date(session.created_at).toLocaleDateString()"></p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </template>
                </div>

            </aside>

            <!-- 2. CENTER: CHAT FLOW (OPTIMIZED) -->
            <main class="flex-1 border-r border-slate-100 flex flex-col bg-white relative overflow-hidden">
                <!-- Inner header for Chat -->
                <div class="px-6 py-4 bg-white border-b border-slate-100 flex items-center justify-between z-20">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-sm font-black text-slate-900 uppercase tracking-tight">Coach Narratif</h1>
                            @php 
                                $chatModel = config('services.gemini.models.chat');
                                $remainingChat = Auth::user()->getAiRemainingPoints($chatModel);
                            @endphp
                            <p class="text-[9px] font-bold {{ $remainingChat > 0 ? 'text-indigo-400' : 'text-rose-400' }} uppercase tracking-widest mt-0.5">
                                {{ $remainingChat > 0 ? "$remainingChat messages restants" : "Quota atteint" }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex-1 flex flex-col w-full overflow-hidden relative">
                    <!-- Messages -->
                    <div class="flex-1 overflow-y-auto custom-scrollbar pt-8 pb-32" id="chat-messages">
                        <div class="max-w-3xl mx-auto px-6 space-y-6">
                            <template x-for="(msg, index) in messages" :key="msg.id">
                                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                                    <div :class="msg.role === 'user' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-50 text-slate-700 border border-slate-100 shadow-sm'" 
                                         class="max-w-[90%] px-5 py-3.5 rounded-2xl text-[13px] leading-relaxed relative">
                                        <p class="whitespace-pre-wrap" x-text="msg.content"></p>
                                    </div>
                                </div>
                            </template>

                            <!-- Typing Indicator -->
                            <div x-show="isTyping" class="flex justify-start">
                                <div class="flex gap-1 p-3 bg-slate-50 border border-slate-100 rounded-2xl shadow-sm">
                                    <div class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0s"></div>
                                    <div class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                                    <div class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Input Area -->
                    <div class="absolute bottom-6 left-0 right-0 px-6 z-30">
                        <div class="max-w-2xl mx-auto bg-white border border-slate-200 rounded-2xl p-2 shadow-xl flex items-end gap-2 focus-within:border-indigo-400 focus-within:ring-2 focus-within:ring-indigo-50 transition-all duration-300">
                            <input type="file" x-ref="documentInput" @change="uploadDocument($event)" class="hidden" accept=".pdf,.doc,.docx,.txt">
                            @php 
                                $ocrModel = config('services.gemini.models.ocr');
                                $remainingOcr = Auth::user()->getAiRemainingPoints($ocrModel);
                            @endphp
                            <button 
                                @click="{{ $remainingOcr > 0 ? '$refs.documentInput.click()' : '' }}"
                                :disabled="isTyping || {{ $remainingOcr > 0 ? 'false' : 'true' }}"
                                class="p-2.5 {{ $remainingOcr > 0 ? 'text-slate-400 hover:text-indigo-600 hover:bg-slate-50' : 'text-slate-200 cursor-not-allowed' }} transition-colors rounded-xl flex flex-col items-center gap-0.5"
                                title="{{ $remainingOcr > 0 ? 'Importer un document' : 'Quota OCR atteint' }}"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                @if($remainingOcr > 0)
                                    <span class="text-[7px] font-black uppercase tracking-tighter opacity-70">{{ $remainingOcr }}</span>
                                @endif
                            </button>
                            <textarea 
                                x-model="newMessage" 
                                x-ref="messageInput"
                                @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); sendMessage(); }"
                                placeholder="Écrivez ici..." 
                                class="flex-1 bg-transparent border-none focus:ring-0 text-sm py-2.5 px-3 custom-scrollbar resize-none max-h-32"
                                rows="1"
                            ></textarea>
                            @php 
                                $chatModel = config('services.gemini.models.chat');
                                $remainingChat = Auth::user()->getAiRemainingPoints($chatModel);
                            @endphp
                            <button 
                                @click="{{ $remainingChat > 0 ? 'sendMessage()' : '' }}"
                                :disabled="!newMessage.trim() || isTyping || {{ $remainingChat > 0 ? 'false' : 'true' }}"
                                class="p-2.5 {{ $remainingChat > 0 ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-slate-200 text-slate-400 cursor-not-allowed' }} rounded-xl transition-all flex flex-col items-center gap-0.5 min-w-[44px]"
                                title="{{ $remainingChat > 0 ? 'Envoyer' : 'Quota atteint' }}"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                </svg>
                                @if($remainingChat > 0)
                                    <span class="text-[7px] font-black uppercase tracking-tighter">{{ $remainingChat }}</span>
                                @endif
                            </button>
                        </div>
                    </div>
                </div>
            </main>

            <!-- 3. RIGHT SIDEBAR: LIVE CV PREVIEW -->
            <aside class="flex-[2] bg-slate-50 flex flex-col overflow-hidden relative border-l border-slate-100">
                <div class="p-4 bg-white border-b border-slate-100 flex items-center justify-between sticky top-0 z-20">
                    <div class="flex items-center gap-3">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Document</span>
                        
                        @if(Auth::user()->isProfileDirty())
                            <div class="h-4 w-[1px] bg-slate-100 mx-1"></div>
                            <form action="{{ route('profile.publish') }}" method="POST">
                                @csrf
                                <button 
                                    type="submit"
                                    class="flex items-center gap-2 px-3 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-[9px] font-black uppercase tracking-widest shadow-lg shadow-amber-500/20 transition-all transform hover:scale-105 animate-pulse-update"
                                    title="Stabiliser le profil et mettre à jour le matching"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    Enregistrer le profil pour les analyses
                                </button>
                            </form>
                        @endif
                    </div>

                    <!-- Notification de suggestions en attente -->
                    <div x-show="pendingChangesCount > 0" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         class="flex items-center gap-2 bg-amber-50 border border-amber-100 px-3 py-1 rounded-full shadow-sm">
                        <span class="flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                        </span>
                        <span class="text-[10px] font-black text-amber-700 uppercase">
                            <span x-text="pendingChangesCount"></span> Suggestions
                        </span>
                        <button @click="scrollToFirstSuggestion()" class="text-[9px] font-black uppercase text-amber-600 hover:text-amber-800 ml-1 underline decoration-2 underline-offset-2">
                            Voir
                        </button>
                    </div>
                </div>

                <!-- NEW PROGRESS RIBBON MOVED TO GLOBAL LAYOUT -->

                <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
                    <div class="cv-paper">
                            <!-- CV HEADER -->
                            <x-cv.header />

                            <!-- SECTION EXPÉRIENCES -->
                            <x-cv.experience />

                            <!-- SECTION FORMATIONS -->
                            <x-cv.education />

                            <!-- SECTION RÉALISATIONS & PROJETS -->
                            <x-cv.projects />

                            <!-- SECTION CERTIFICATIONS -->
                            <x-cv.certifications />
                            
                            <!-- SECTION LANGUES -->
                            <x-cv.languages />

                            <!-- SECTION ENGAGEMENT -->
                            <x-cv.volunteer />

                            <!-- SECTION POINTS FORTS (FACTS) -->
                            <x-cv.facts />

                            <!-- INTERESTS -->
                            <x-cv.interests />

                            <!-- SECTION COMPÉTENCES -->
                            <x-cv.skills />
                        </div>
                    </div>
                </aside>

        </div>
    </div>


</x-app-layout>
