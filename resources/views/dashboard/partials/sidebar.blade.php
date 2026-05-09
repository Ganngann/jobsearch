<aside class="w-80 border-r border-slate-200 bg-white flex flex-col shrink-0 overflow-y-auto custom-scrollbar">
    <div class="p-6 space-y-10">

        <!-- Section: Maturité (Gamification) -->
        @include('dashboard.partials.maturity-indicator')

        <!-- Section: Top Métiers -->
        @include('dashboard.partials.metier-filter')

        <!-- Section: Top Employeurs -->
        @include('dashboard.partials.employer-filter')

    </div>
</aside>
