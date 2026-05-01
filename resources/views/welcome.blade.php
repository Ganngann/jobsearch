<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forem Matcher AI - Trouvez votre job idéal</title>

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
                    <a href="#" class="text-sm font-medium text-slate-300 hover:text-white transition-colors nav-link">Offres</a>
                    <a href="#" class="text-sm font-medium text-slate-300 hover:text-white transition-colors nav-link">Comment ça marche</a>
                    <a href="#" class="text-sm font-medium text-slate-300 hover:text-white transition-colors nav-link">Tarifs</a>
                </div>

                <div class="flex items-center space-x-4">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="px-5 py-2.5 rounded-full text-sm font-semibold bg-white/10 hover:bg-white/20 transition-all border border-white/10">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-medium text-slate-300 hover:text-white transition-colors">Connexion</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="px-5 py-2.5 rounded-full text-sm font-semibold bg-indigo-600 hover:bg-indigo-500 transition-all shadow-lg shadow-indigo-500/25">S'inscrire</a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <main>
        <!-- Hero Section -->
        <section class="relative pt-40 pb-20 lg:pt-56 lg:pb-32 px-4">
            <div class="max-w-5xl mx-auto text-center">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-bold mb-6 tracking-wider uppercase">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                    </span>
                    Propulsé par l'IA générative
                </div>
                <h1 class="text-5xl lg:text-7xl font-extrabold tracking-tight mb-8">
                    Trouvez le job qui vous <br>
                    <span class="gradient-text italic">correspond vraiment</span>
                </h1>
                <p class="text-lg lg:text-xl text-slate-400 max-w-2xl mx-auto mb-12 leading-relaxed">
                    Forem Matcher utilise l'intelligence artificielle pour analyser vos compétences et vous proposer les meilleures offres du Forem, avec un score de compatibilité personnalisé.
                </p>

                <!-- Glass Search Bar -->
                <div class="max-w-2xl mx-auto p-2 glass rounded-2xl border border-white/10 shadow-2xl flex flex-col md:flex-row gap-2">
                    <div class="flex-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" placeholder="Développeur, Menuisier, Infirmier..." class="block w-full pl-11 pr-3 py-4 bg-transparent border-none focus:ring-0 text-white placeholder-slate-500 text-sm">
                    </div>
                    <div class="md:w-1/3 relative border-t md:border-t-0 md:border-l border-white/10">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <input type="text" placeholder="Namur, Bruxelles..." class="block w-full pl-11 pr-3 py-4 bg-transparent border-none focus:ring-0 text-white placeholder-slate-500 text-sm">
                    </div>
                    <button class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-4 px-8 rounded-xl transition-all shadow-lg shadow-indigo-500/25">
                        Chercher
                    </button>
                </div>

                <!-- Stats -->
                <div class="mt-20 grid grid-cols-2 md:grid-cols-4 gap-8 max-w-4xl mx-auto">
                    <div>
                        <div class="text-3xl font-bold text-white mb-1">12k+</div>
                        <div class="text-sm text-slate-500 uppercase tracking-widest font-semibold">Offres actives</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-white mb-1">98%</div>
                        <div class="text-sm text-slate-500 uppercase tracking-widest font-semibold">Match score</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-white mb-1">500+</div>
                        <div class="text-sm text-slate-500 uppercase tracking-widest font-semibold">Métiers</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-white mb-1">24/7</div>
                        <div class="text-sm text-slate-500 uppercase tracking-widest font-semibold">Sync Forem</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-24 px-4 bg-slate-900/50">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-3xl lg:text-4xl font-bold mb-4">Pourquoi choisir Forem Matcher ?</h2>
                    <p class="text-slate-400 max-w-2xl mx-auto">Notre technologie vous fait gagner du temps en filtrant le bruit et en se concentrant sur ce qui compte : votre potentiel.</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="p-8 glass rounded-3xl border border-white/5 transition-all duration-300 card-hover">
                        <div class="w-12 h-12 bg-indigo-500/10 rounded-2xl flex items-center justify-center text-indigo-400 mb-6">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-4 text-white">Analyse Sémantique</h3>
                        <p class="text-slate-400 leading-relaxed">
                            Nous ne nous contentons pas de mots-clés. Notre IA comprend le contexte de votre expérience et les exigences réelles du recruteur.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="p-8 glass rounded-3xl border border-white/5 transition-all duration-300 card-hover">
                        <div class="w-12 h-12 bg-purple-500/10 rounded-2xl flex items-center justify-center text-purple-400 mb-6">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-4 text-white">Sync en Temps Réel</h3>
                        <p class="text-slate-400 leading-relaxed">
                            Connecté directement aux API du Forem, notre système vous informe dès qu'une opportunité pertinente est publiée.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="p-8 glass rounded-3xl border border-white/5 transition-all duration-300 card-hover">
                        <div class="w-12 h-12 bg-rose-500/10 rounded-2xl flex items-center justify-center text-rose-400 mb-6">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-4 text-white">Dimension Humaine</h3>
                        <p class="text-slate-400 leading-relaxed">
                            Parce que vous êtes plus qu'un CV, nous évaluons aussi vos soft-skills et vos valeurs pour garantir un match durable.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20 px-4">
            <div class="max-w-4xl mx-auto glass p-12 lg:p-20 rounded-[3rem] border border-white/10 text-center relative overflow-hidden">
                <div class="absolute -top-24 -left-24 w-64 h-64 bg-indigo-600/20 blur-[80px]"></div>
                <div class="absolute -bottom-24 -right-24 w-64 h-64 bg-rose-600/20 blur-[80px]"></div>

                <h2 class="text-3xl lg:text-5xl font-bold mb-8 relative z-10">Prêt à booster votre carrière ?</h2>
                <p class="text-slate-400 mb-12 text-lg relative z-10">Rejoignez des milliers de candidats qui utilisent déjà Forem Matcher pour trouver leur voie.</p>
                <div class="flex flex-col sm:flex-row justify-center gap-4 relative z-10">
                    <a href="{{ route('register') }}" class="px-10 py-5 bg-white text-slate-900 font-bold rounded-2xl hover:bg-slate-100 transition-all transform hover:scale-105">
                        Commencer gratuitement
                    </a>
                    <a href="#" class="px-10 py-5 glass border border-white/20 text-white font-bold rounded-2xl hover:bg-white/10 transition-all">
                        Voir la démo
                    </a>
                </div>
            </div>
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
</body>
</html>
