<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MatchChunkJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public \App\Models\User $user,
        public array $jobOfferIds
    ) {}

    /**
     * Execute the job.
     */
    public function handle(\App\Services\MatchingService $matchingService): void
    {
        $offers = \App\Models\JobOffer::whereIn('id', $this->jobOfferIds)->get();
        
        foreach ($offers as $offer) {
            $matchingService->match($this->user, $offer, false, false);
        }
    }
}
