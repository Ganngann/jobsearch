<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\UpdateLastSeenMiddleware::class,
        ]);
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // Scan Flash toutes les 5 minutes pour les nouveautés
        $schedule->command('forem:scan --mode=flash')->everyFiveMinutes();

        // Scan Cycle toutes les 15 minutes pour rafraîchir tout le catalogue
        $schedule->command('forem:scan --mode=cycle')->everyFifteenMinutes();

        // Pull Worker tourne en continu pour les détails (accéléré pour rattraper le retard)
        $schedule->command('forem:pull-worker --sleep=2 --limit=30')->everyMinute()->withoutOverlapping(10);

        // Chasseur de Pépites IA (Analyse auto du meilleur match pour les utilisateurs actifs)
        $schedule->command('forem:auto-ai')->everyFifteenMinutes();
    })->create();
