<x-app-layout>
    <div 
        x-data="dashboardApp({
            initialSelectedId: {{ Js::from($jobOffers->first()?->forem_id) }},
            csrfToken: {{ Js::from(csrf_token()) }},
            filters: {
                sort: {{ Js::from(request('sort', 'score_desc')) }},
                min_score: {{ Js::from(request('min_score', 0)) }},
                metier_id: {{ Js::from(request('metier_id')) }},
                employer_id: {{ Js::from(request('employer_id')) }},
                rome: {{ Js::from(request('rome')) }},
                q: {{ Js::from(request('q')) }}
            }
        })" 
        class="h-[calc(100vh-112px)] flex overflow-hidden bg-slate-50"
    >
        
        <!-- SIDEBAR: Filtres & Exploration -->
        @include('dashboard.partials.sidebar')

        <!-- MIDDLE: Liste des offres -->
        @include('dashboard.partials.job-list')

        <!-- RIGHT: Panneau de prévisualisation -->
        @include('dashboard.partials.preview-panel')

        <!-- TOAST CONTAINER (JIT Feedback) -->
        <div 
            id="toast-container" 
            class="fixed bottom-8 right-8 z-[200] flex flex-col gap-3 pointer-events-none"
        ></div>

    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

        @keyframes score-bounce {
            0% { transform: scale(1); }
            50% { transform: scale(1.15); filter: brightness(1.2); }
            100% { transform: scale(1); }
        }
        .animate-score-change {
            animation: score-bounce 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes toast-in {
            0% { transform: translateX(100%) scale(0.9); opacity: 0; }
            100% { transform: translateX(0) scale(1); opacity: 1; }
        }
        .animate-toast-in {
            animation: toast-in 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
    </style>
</x-app-layout>
