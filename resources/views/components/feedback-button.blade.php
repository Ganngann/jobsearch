<div x-data="feedbackSystem({
    csrfToken: '{{ csrf_token() }}',
    routes: {
        store: '{{ route('feedback.store') }}'
    }
})" class="fixed bottom-6 right-6 z-[100]">
    <!-- Bouton Flottant -->
    <button 
        @click="open = !open" 
        class="flex items-center justify-center w-14 h-14 bg-indigo-600 text-white rounded-full shadow-2xl hover:bg-indigo-500 hover:scale-110 transition-all group relative"
        title="Donne ton avis"
    >
        <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
        </svg>
        <svg x-show="open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        <span class="absolute -top-1 -right-1 flex h-3 w-3" x-show="!hasInteracted">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500"></span>
        </span>
    </button>

    <!-- Panneau de Feedback -->
    <div 
        x-show="open" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        @click.away="open = false"
        class="absolute bottom-20 right-0 w-80 bg-white rounded-3xl shadow-2xl border border-slate-100 p-6 overflow-hidden text-slate-900"
        x-cloak
    >
        <div x-show="!sent">
            <h3 class="text-lg font-black text-slate-900 mb-1">Ton avis m'intéresse !</h3>
            <p class="text-xs text-slate-400 mb-6">Un bug ? Une idée ? Dis-moi tout, c'est comme ça que j'améliore l'outil.</p>

            <div class="space-y-4">
                <div class="flex gap-2">
                    <button @click="type = 'feedback'" :class="type === 'feedback' ? 'bg-indigo-50 border-indigo-200 text-indigo-600' : 'bg-slate-50 border-transparent text-slate-400'" class="flex-1 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all">Avis</button>
                    <button @click="type = 'bug'" :class="type === 'bug' ? 'bg-rose-50 border-rose-200 text-rose-600' : 'bg-slate-50 border-transparent text-slate-400'" class="flex-1 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all">Bug</button>
                    <button @click="type = 'idea'" :class="type === 'idea' ? 'bg-amber-50 border-amber-200 text-amber-600' : 'bg-slate-50 border-transparent text-slate-400'" class="flex-1 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all">Idée</button>
                </div>

                <textarea 
                    x-model="message" 
                    placeholder="Tape ton message ici..." 
                    class="w-full h-32 bg-slate-50 border-0 rounded-2xl p-4 text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 transition-all resize-none"
                ></textarea>

                <button 
                    @click="sendFeedback()" 
                    :disabled="loading || message.length < 5"
                    class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold text-sm shadow-xl shadow-indigo-100 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                >
                    <span x-show="!loading">Envoyer mon retour</span>
                    <span x-show="loading" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Envoi...
                    </span>
                </button>
            </div>
        </div>

        <div x-show="sent" class="py-8 text-center" x-cloak>
            <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3 class="text-lg font-black text-slate-900 mb-1">C'est envoyé !</h3>
            <p class="text-sm text-slate-400 mb-6">Merci beaucoup pour ton aide. Je regarde ça très vite.</p>
            <button @click="open = false; setTimeout(() => sent = false, 500)" class="text-sm font-bold text-indigo-600 hover:text-indigo-700">Fermer</button>
        </div>
    </div>
</div>



<style>
    [x-cloak] { display: none !important; }
</style>
