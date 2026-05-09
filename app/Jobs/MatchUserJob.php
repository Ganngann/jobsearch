<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\JobOffer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MatchUserJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public User $user)
    {
        $this->onQueue('low'); // On met le matching massif en priorité basse
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("MASS_MATCH: Starting chunking for user #{$this->user->id}");

        JobOffer::where('status', 'active')
            ->where('is_detailed', true)
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>=', now());
            })
            ->chunkById(100, function($offers, $index) {
                // On délègue le matching de chaque lot à un MatchChunkJob
                // On espace les calculs pour lisser la charge sur la DB
                MatchChunkJob::dispatch($this->user, $offers->pluck('id')->toArray())
                    ->delay(now()->addSeconds($index * 2));
            });

        Log::info("MASS_MATCH: All chunks dispatched for user #{$this->user->id}");
    }
}
