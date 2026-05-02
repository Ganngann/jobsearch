<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyzeJobOffer implements ShouldQueue
{
    use Queueable;

    protected $user;
    protected $jobOffer;
    protected $match;

    /**
     * Create a new job instance.
     */
    public function __construct(\App\Models\User $user, \App\Models\JobOffer $jobOffer, \App\Models\UserMatch $match)
    {
        $this->user = $user;
        $this->jobOffer = $jobOffer;
        $this->match = $match;
    }

    /**
     * Execute the job.
     */
    public function handle(\App\Services\MatchingService $matchingService): void
    {
        $this->match->update(['ai_status' => 'processing']);

        try {
            $success = $matchingService->performAiAnalysis($this->user, $this->jobOffer, $this->match);
            
            if ($success) {
                $this->match->update(['ai_status' => 'completed']);
            } else {
                $this->match->update(['ai_status' => 'failed']);
            }
        } catch (\Exception $e) {
            $this->match->update(['ai_status' => 'failed']);
            throw $e;
        }
    }
}
