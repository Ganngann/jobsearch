<?php

namespace Tests\Unit\Jobs;

use App\Jobs\MatchUserJob;
use App\Models\User;
use App\Models\JobOffer;
use App\Jobs\MatchChunkJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MatchUserJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_chunks_for_active_job_offers()
    {
        Queue::fake();
        Log::shouldReceive('info')->twice();

        $user = User::factory()->create();

        // Active and valid offer
        JobOffer::factory()->create([
            'status' => 'active',
            'is_detailed' => true,
            'expires_at' => now()->addDays(5),
        ]);

        // Active but not detailed
        JobOffer::factory()->create([
            'status' => 'active',
            'is_detailed' => false,
            'expires_at' => now()->addDays(5),
        ]);

        // Inactive offer
        JobOffer::factory()->create([
            'status' => 'inactive',
            'is_detailed' => true,
            'expires_at' => now()->addDays(5),
        ]);

        $job = new MatchUserJob($user);
        $job->handle();

        Queue::assertPushed(MatchChunkJob::class, 1);

        $this->assertEquals('low', $job->queue);
    }
}
