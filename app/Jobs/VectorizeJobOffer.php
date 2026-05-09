<?php

namespace App\Jobs;

use App\Models\JobOffer;
use App\Services\VectorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VectorizeJobOffer implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobOffer;

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return (string) $this->jobOffer->id;
    }

    /**
     * Create a new job instance.
     */
    public function __construct(JobOffer $jobOffer)
    {
        $this->jobOffer = $jobOffer;
    }

    /**
     * Execute the job.
     */
    public function handle(VectorService $vectorService): void
    {
        // On ne vectorise que si c'est encore nécessaire
        if ($this->jobOffer->vector_embedding) {
            return;
        }

        try {
            $vectorService->updateJobVector($this->jobOffer);
            Log::info("Job #{$this->jobOffer->id} vectorized successfully via queue.");
        } catch (\Exception $e) {
            Log::error("Failed to vectorize job #{$this->jobOffer->id}: " . $e->getMessage());
            // On ne retry pas automatiquement pour éviter de vider le quota API en boucle
            // si c'est une erreur systématique.
        }
    }
}
