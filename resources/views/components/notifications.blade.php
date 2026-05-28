<div x-data="{ 
        notifications: [],
        confirmModal: {
            show: false,
            title: '',
            message: '',
            resolve: null
        },
        add(message, type = 'success') {
            const id = Date.now();
            this.notifications.push({ id, message, type });
            setTimeout(() => {
                this.notifications = this.notifications.filter(n => n.id !== id);
            }, 5000);
        },
        async ask(title, message) {
            this.confirmModal.title = title;
            this.confirmModal.message = message;
            this.confirmModal.show = true;
            return new Promise((resolve) => {
                this.confirmModal.resolve = resolve;
            });
        },
        init() {
            @if(session('status'))
                this.add("{{ session('status') }}", 'success');
            @endif
            @if(session('error'))
                this.add("{{ session('error') }}", 'error');
            @endif
        },
        async handleConfirm(detail) {
            const result = await this.ask(detail.title, detail.message);
            if (result) detail.callback();
        }
     }"
     @notify.window="add($event.detail.message, $event.detail.type)"
     @confirm.window="handleConfirm($event.detail)"
     class="fixed inset-0 pointer-events-none z-[9999]">
    
    {{-- Toasts Container --}}
    <div class="fixed top-4 right-4 flex flex-col gap-2 w-full max-w-sm">
        <template x-for="n in notifications" :key="n.id">
            <div x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-x-8"
                 x-transition:enter-end="opacity-100 transform translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform translate-x-0"
                 x-transition:leave-end="opacity-0 transform translate-x-8"
                 :class="{
                    'bg-white border-l-4 border-green-500 shadow-2xl': n.type === 'success',
                    'bg-white border-l-4 border-red-500 shadow-2xl': n.type === 'error',
                    'bg-white border-l-4 border-amber-500 shadow-2xl': n.type === 'warning'
                 }"
                 class="p-4 rounded-2xl flex items-start gap-3 border border-gray-100 pointer-events-auto">
                
                <div class="flex-shrink-0 mt-0.5">
                    <template x-if="n.type === 'success'">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </template>
                    <template x-if="n.type === 'error'">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </template>
                </div>

                <div class="flex-1">
                    <p class="text-sm font-bold text-gray-900" x-text="n.message"></p>
                </div>

                <button @click="notifications = notifications.filter(notif => notif.id !== n.id)" class="text-gray-400 hover:text-gray-600" aria-label="Fermer la notification">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </template>
    </div>

    {{-- Confirmation Modal --}}
    <div x-show="confirmModal.show" 
         class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 pointer-events-auto"
         style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="bg-white rounded-[2.5rem] p-8 max-w-sm w-full shadow-2xl border border-gray-100 transform transition-all"
             x-show="confirmModal.show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0">
            
            <div class="w-16 h-16 bg-amber-50 rounded-2xl flex items-center justify-center mb-6">
                <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>

            <h3 class="text-xl font-black text-gray-900 mb-2" x-text="confirmModal.title"></h3>
            <p class="text-sm text-gray-500 leading-relaxed mb-8" x-text="confirmModal.message"></p>

            <div class="flex gap-3">
                <button @click="confirmModal.show = false; confirmModal.resolve(false)" 
                        class="flex-1 px-6 py-3 rounded-2xl font-bold text-sm text-gray-400 hover:bg-gray-50 transition-colors">
                    Annuler
                </button>
                <button @click="confirmModal.show = false; confirmModal.resolve(true)" 
                        class="flex-1 px-6 py-3 rounded-2xl font-bold text-sm bg-indigo-600 text-white shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-colors">
                    Confirmer
                </button>
            </div>
        </div>
    </div>
</div>
