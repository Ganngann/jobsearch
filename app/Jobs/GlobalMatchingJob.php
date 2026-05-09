<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\MatchingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GlobalMatchingJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(MatchingService $matchingService): void
    {
        $users = User::whereNotNull('vector_embedding')->get();
        
        Log::info("GLOBAL_SYNC: Starting background matching for " . $users->count() . " users.");
        
        foreach ($users as $user) {
            $matchingService->triggerMassMatch($user);
        }
        
        Log::info("GLOBAL_SYNC: All users matching tasks have been dispatched.");
    }
}
