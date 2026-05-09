<main class="flex-1 flex flex-col border-r border-slate-200 min-w-[450px]">
    <!-- Header de liste -->
    @include('dashboard.partials.list-header')

    <!-- Scrollable List -->
    <div id="offers-scroll-container" class="flex-1 overflow-y-auto custom-scrollbar p-4 space-y-3 bg-slate-50/50">
        <div id="offers-container" class="space-y-3">
            @include('job-offers.partials.list-items', ['jobOffers' => $jobOffers])
        </div>

        <!-- Sentinel for Infinite Scroll -->
        <div 
            x-intersect.margin.200px="loadMore()" 
            x-show="!noMoreData" 
            class="py-10 flex flex-col items-center justify-center space-y-4"
        >
            <template x-if="loadingMore">
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-4">Chargement de la suite...</p>
                </div>
            </template>
        </div>

        <div x-show="noMoreData" class="py-10 text-center">
            <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest italic">Toutes les offres ont été chargées</p>
        </div>
    </div>
</main>
