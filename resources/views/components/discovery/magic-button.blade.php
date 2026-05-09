<div class="flex justify-center mb-20"
     x-data="{
        messages: [
            'Analyse de votre profil narratif...',
            'Exploration des 532 fiches ROME...',
            'Détection des talents cachés...',
            'Calcul des ponts sémantiques...',
            'Finalisation des propositions personnalisées...'
        ],
        messageInterval: null,
        async getSuggestions() {
            $store.discovery.loading = true;
            $store.discovery.errorMessage = '';
            $store.discovery.suggestions = [];
            
            let msgIndex = 0;
            $store.discovery.loadingMessage = this.messages[0];
            this.messageInterval = setInterval(() => {
                msgIndex = (msgIndex + 1) % this.messages.length;
                $store.discovery.loadingMessage = this.messages[msgIndex];
            }, 2500);

            const data = await $store.discovery.get($store.discovery.config.suggestRoute);
            
            clearInterval(this.messageInterval);
            $store.discovery.loading = false;

            if (data?.status === 'error') {
                $store.discovery.errorMessage = data.message;
            } else if (data?.suggestions) {
                $store.discovery.suggestions = data.suggestions;
            } else {
                $store.discovery.errorMessage = 'Une erreur réseau est survenue.';
            }
        }
     }">
    @php 
        $chatModel = config('services.gemini.models.chat');
        $remainingChat = Auth::user()->getAiRemainingPoints($chatModel);
    @endphp
    <button 
        @click="{{ $remainingChat > 0 ? 'getSuggestions()' : '' }}"
        :disabled="$store.discovery.loading || {{ $remainingChat > 0 ? 'false' : 'true' }}"
        {{ $attributes->merge(['class' => 'relative group px-12 py-6 ' . ($remainingChat > 0 ? 'bg-gradient-to-r from-indigo-600 to-violet-600' : 'bg-slate-700') . ' rounded-full text-white font-black text-2xl shadow-2xl shadow-indigo-200 transition-all duration-300 ' . ($remainingChat > 0 ? 'hover:scale-105 active:scale-95' : 'opacity-50 cursor-not-allowed') . ' overflow-hidden']) }}
    >
        <span class="relative z-10 flex flex-col items-center">
            <span class="flex items-center gap-3">
                <template x-if="!$store.discovery.loading">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </template>
                <template x-if="$store.discovery.loading">
                    <svg class="w-8 h-8 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </template>
                <span x-text="$store.discovery.loading ? 'Exploration en cours...' : ($store.discovery.suggestions.length > 0 ? 'Nouvelle exploration' : 'Surprends-moi !')"></span>
            </span>
            @if($remainingChat > 0)
                <span class="text-[10px] font-black uppercase tracking-[0.2em] opacity-70 mt-1">{{ $remainingChat }} explorations restantes aujourd'hui</span>
            @else
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-rose-300 mt-1">Quota quotidien atteint</span>
            @endif
        </span>
        @if($remainingChat > 0)
            <div class="absolute inset-0 bg-gradient-to-r from-violet-600 to-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        @endif
    </button>
</div>
