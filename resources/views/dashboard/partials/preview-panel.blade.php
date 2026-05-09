<section class="flex-1 bg-white relative hidden lg:flex flex-col">
    <div x-show="!selectedId" class="h-full flex flex-col items-center justify-center p-12 text-center bg-slate-50/50">

        <div class="w-24 h-24 bg-white rounded-3xl shadow-xl shadow-slate-200 flex items-center justify-center mb-6">
            <svg class="w-10 h-10 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
        </div>
        <h3 class="text-lg font-black text-slate-900">Aucune offre sélectionnée</h3>
        <p class="mt-2 text-sm text-slate-400 font-medium max-w-xs">Cliquez sur une offre dans la liste de gauche pour voir l'analyse détaillée.</p>
    </div>

    <div x-show="selectedId" class="h-full relative">
        <!-- Loader -->
        <div x-show="previewLoading" class="absolute inset-0 z-50 bg-white/80 backdrop-blur-sm flex items-center justify-center">
            <div class="flex flex-col items-center">
                <div class="w-12 h-12 border-4 border-indigo-100 border-t-indigo-600 rounded-full animate-spin"></div>
                <p class="mt-4 text-xs font-black text-indigo-600 uppercase tracking-widest">Analyse en cours...</p>
            </div>
        </div>

        <!-- Preview Content Area -->
        <div x-html="previewHtml" class="h-full">
            <!-- Le contenu sera injecté ici via AJAX -->
        </div>
    </div>
</section>
