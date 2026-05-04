<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forem Matcher AI - Trouvez votre job idéal</title>
    <meta name="description" content="Découvrez les meilleures opportunités d'emploi au Forem grâce à l'intelligence artificielle. Analyse de profil narrative et matching sémantique pour une carrière qui vous ressemble.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --primary: #6366f1;
            --secondary: #a855f7;
            --accent: #f43f5e;
            --bg-dark: #0f172a;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-dark);
            color: #f8fafc;
            overflow-x: hidden;
        }

        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px border rgba(255, 255, 255, 0.1);
        }

        .gradient-text {
            background: linear-gradient(135deg, #818cf8 0%, #c084fc 50%, #fb7185 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .blob {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, rgba(99, 102, 241, 0) 70%);
            border-radius: 50%;
            filter: blur(60px);
            animation: move 20s infinite alternate;
        }

        .blob-2 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.15) 0%, rgba(168, 85, 247, 0) 70%);
            right: -100px;
            top: 20%;
            animation: move 25s infinite alternate-reverse;
        }

        @keyframes move {
            from { transform: translate(-10%, -10%); }
            to { transform: translate(10%, 10%); }
        }

        .card-hover:hover {
            transform: translateY(-5px);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
        }

        .nav-link {
            position: relative;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s;
        }

        .nav-link:hover::after {
            width: 100%;
        }
    </style>
</head>
<body class="antialiased">
    <div class="hero-bg">
        <div class="blob"></div>
        <div class="blob blob-2"></div>
    </div>

    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 glass border-b border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/20">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight">Forem<span class="text-indigo-400">Matcher</span></span>
                </div>

                <div class="hidden md:flex items-center space-x-8">
                    {{-- Liens masqués pour les invités car ils nécessitent un profil --}}
                </div>

                <div class="flex items-center space-x-4">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="px-5 py-2.5 rounded-full text-sm font-semibold bg-white/10 hover:bg-white/20 transition-all border border-white/10">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-medium text-slate-300 hover:text-white transition-colors">Connexion</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="px-5 py-2.5 rounded-full text-sm font-semibold bg-indigo-600 hover:bg-indigo-500 transition-all shadow-lg shadow-indigo-500/25">Rejoindre</a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <main>
        <!-- Hero Section -->
        <section class="relative pt-32 pb-20 px-4 overflow-hidden">
            <div class="max-w-4xl mx-auto text-center relative z-10">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-bold mb-8 uppercase tracking-widest">
                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                    Outil en Beta • Partage entre amis
                </div>
                
                <h1 class="text-5xl lg:text-7xl font-black mb-8 leading-[1.1] tracking-tight">
                    Le Forem, mais en <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-violet-400 italic">beaucoup plus malin.</span>
                </h1>
                
                <p class="text-xl text-slate-400 mb-12 max-w-2xl mx-auto leading-relaxed">
                    J'ai bricolé cet outil pour nous éviter de perdre 2h par jour sur le site du Forem. L'idée est simple : l'IA lit les offres pour toi et te dit si ça vaut le coup de postuler.
                </p>

                <div class="flex justify-center">
                    <a href="{{ route('register') }}" class="px-12 py-6 bg-indigo-600 text-white font-bold rounded-2xl hover:bg-indigo-500 transition-all shadow-2xl shadow-indigo-500/40 transform hover:scale-105">
                        Rejoindre l'outil
                    </a>
                </div>
            </div>
        </section>

        <!-- Stats -->
        <section class="pb-24 px-4">
            <div class="max-w-4xl mx-auto py-12 px-8 glass rounded-[2.5rem] border border-white/5">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                    <div>
                        <div class="text-3xl font-black text-white mb-1">{{ $stats['jobs'] ?? '3k+' }}</div>
                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Offres Scannées</div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-white mb-1">98%</div>
                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Précision IA</div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-white mb-1">{{ $stats['metiers'] ?? '200+' }}</div>
                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Métiers</div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-white mb-1">24/7</div>
                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Sync Forem</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Steps Section -->
        <section class="py-24 px-4 border-t border-white/5">
            <div class="max-w-5xl mx-auto">
                <div class="text-center mb-20">
                    <h2 class="text-3xl lg:text-4xl font-bold mb-4">Comment on l'utilise ?</h2>
                    <p class="text-slate-400 max-w-xl mx-auto">C'est pas un site de recherche classique, c'est un assistant personnel. Voici les 4 étapes pour s'en servir :</p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Step 1 -->
                    <div class="p-8 glass rounded-3xl border border-white/5 relative overflow-hidden group">
                        <div class="text-6xl font-black text-white/5 absolute -top-2 -right-2 transition-all group-hover:text-indigo-500/10">01</div>
                        <h3 class="text-lg font-bold mb-4 text-indigo-400">Accès Privé</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            On commence par créer un compte. C'est nécessaire pour que tes données (CV, préférences) restent privées et sécurisées.
                        </p>
                    </div>

                    <!-- Step 2 -->
                    <div class="p-8 glass rounded-3xl border border-white/5 relative overflow-hidden group">
                        <div class="text-6xl font-black text-white/5 absolute -top-2 -right-2 transition-all group-hover:text-indigo-500/10">02</div>
                        <h3 class="text-lg font-bold mb-4 text-indigo-400">Ton Récit</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Tu discutes avec le Coach IA ou tu importes ton CV. L'IA va créer ton "Profil Augmenté" (ce que tu sais faire ET ce que tu aimes).
                        </p>
                    </div>

                    <!-- Step 3 -->
                    <div class="p-8 glass rounded-3xl border border-white/5 relative overflow-hidden group">
                        <div class="text-6xl font-black text-white/5 absolute -top-2 -right-2 transition-all group-hover:text-indigo-500/10">03</div>
                        <h3 class="text-lg font-bold mb-4 text-indigo-400">Scan Forem</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            L'outil scanne en permanence le site du Forem. Il récupère toutes les nouvelles offres et les compare instantanément à ton profil.
                        </p>
                    </div>

                    <!-- Step 4 -->
                    <div class="p-8 glass rounded-3xl border border-white/5 relative overflow-hidden group">
                        <div class="text-6xl font-black text-white/5 absolute -top-2 -right-2 transition-all group-hover:text-indigo-500/10">04</div>
                        <h3 class="text-lg font-bold mb-4 text-indigo-400">Le Verdict</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Tu consultes ton tableau de bord. Pour chaque offre, l'IA te donne un score et t'explique en 3 points pourquoi ça te correspond (ou pas).
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Concept Detail -->
        <section class="py-24 px-4 bg-slate-950/30">
            <div class="max-w-4xl mx-auto">
                <div class="grid md:grid-cols-2 gap-16 items-center">
                    <div class="relative">
                        <div class="aspect-square glass rounded-[40px] border border-white/10 flex items-center justify-center p-12 overflow-hidden shadow-inner">
                            <div class="absolute inset-0 bg-gradient-to-br from-indigo-600/10 to-violet-600/10"></div>
                            <div class="relative z-10 space-y-6">
                                <div class="p-4 bg-white/5 rounded-2xl border border-white/10 blur-[1px] opacity-40">
                                    <div class="h-2 w-20 bg-white/20 rounded mb-2"></div>
                                    <div class="h-2 w-32 bg-white/10 rounded"></div>
                                </div>
                                <div class="p-6 bg-indigo-600/20 rounded-2xl border border-indigo-500/30 scale-110 shadow-2xl">
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="w-3 h-3 rounded-full bg-indigo-400"></div>
                                        <div class="h-3 w-24 bg-white/40 rounded"></div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="h-2 w-full bg-white/20 rounded"></div>
                                        <div class="h-2 w-5/6 bg-white/20 rounded"></div>
                                    </div>
                                </div>
                                <div class="p-4 bg-white/5 rounded-2xl border border-white/10 blur-[1px] opacity-40">
                                    <div class="h-2 w-24 bg-white/20 rounded mb-2"></div>
                                    <div class="h-2 w-16 bg-white/10 rounded"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold mb-6 text-white leading-tight">Pourquoi s'embêter avec un "récit" ?</h2>
                        <p class="text-slate-400 mb-6 leading-relaxed">
                            Un CV classique ne dit pas tout. Tu peux être un excellent comptable mais vouloir travailler dans une petite équipe créative, ou détester les déplacements longs.
                        </p>
                        <p class="text-slate-400 leading-relaxed">
                            En discutant avec l'IA (étape 2), tu lui donnes ces nuances. Résultat : elle élimine les offres qui semblent bonnes sur le papier mais qui te rendraient malheureux.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA -->
        <section class="py-24 px-4 text-center">
            <h2 class="text-3xl font-bold mb-8 italic">On essaie ?</h2>
            <p class="text-slate-400 mb-10 max-w-lg mx-auto">C'est gratuit, c'est fait maison, et ça peut te sauver quelques heures de scroll intensif.</p>
            <a href="{{ route('register') }}" class="inline-flex items-center gap-3 px-12 py-6 bg-white text-slate-900 font-bold rounded-2xl hover:bg-slate-100 transition-all transform hover:scale-105">
                Créer mon accès
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </section>
    </main>

    <footer class="py-12 border-t border-white/5 text-center text-slate-500 text-sm">
        <div class="max-w-7xl mx-auto px-4">
            <div class="mb-8">
                 <span class="text-lg font-bold tracking-tight text-white">Forem<span class="text-indigo-400">Matcher</span></span>
            </div>
            <p>&copy; 2026 Forem Matcher AI. Développé avec passion pour l'emploi en Belgique.</p>
        </div>
    </footer>
    <x-feedback-button />
</body>
</html>
