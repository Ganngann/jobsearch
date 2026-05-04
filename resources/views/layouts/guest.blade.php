<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased" style="font-family: 'Outfit', sans-serif;">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-[#020617]">
            <div class="mb-8">
                <a href="/" class="flex items-center gap-2">
                    <div class="w-12 h-12 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/20">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span class="text-2xl font-bold tracking-tight text-white">Forem<span class="text-indigo-400">Matcher</span></span>
                </a>
            </div>

            <div class="w-full sm:max-w-md px-8 py-10 bg-white/5 border border-white/10 backdrop-blur-xl shadow-2xl overflow-hidden sm:rounded-[2.5rem]">
                {{ $slot }}
            </div>
            
            <div class="mt-8 text-slate-500 text-sm">
                Bricolé avec passion pour nous tous.
            </div>
        </div>
    </body>
</html>
