<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\MatchingService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecalculateMatchesJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return (string) $this->user->id;
    }

    /**
     * Create a new job instance.
     */
    public function __construct(public User $user) {}

    /**
     * Execute the job.
     */
    public function handle(MatchingService $matchingService): void
    {
        ini_set('memory_limit', '512M');
        
        \Log::info("Démarrage du recalcul global pour l'utilisateur: {$this->user->email}");
        
        $matchingService->triggerMassMatch($this->user);
        
        \Log::info("Fin du recalcul global pour l'utilisateur: {$this->user->email}");
        
        // Nettoyage manuel de la mémoire
        gc_collect_cycles();
    }
}
