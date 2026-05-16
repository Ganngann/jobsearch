<?php

namespace Tests\Unit\Jobs;

use App\Jobs\GlobalMatchingJob;
use App\Models\User;
use App\Services\MatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class GlobalMatchingJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_triggers_mass_match_for_users_with_vector_embedding()
    {
        Log::shouldReceive('info')->twice();

        $userWithVector = User::factory()->create(['vector_embedding' => '[0.1]']);
        $userWithoutVector = User::factory()->create(['vector_embedding' => null]);

        $matchingServiceMock = Mockery::mock(MatchingService::class);
        $matchingServiceMock->shouldReceive('triggerMassMatch')
                            ->once()
                            ->with(Mockery::on(function ($arg) use ($userWithVector) {
                                return $arg->id === $userWithVector->id;
                            }));

        $job = new GlobalMatchingJob();
        $job->handle($matchingServiceMock);

        $this->assertTrue(true);
    }
}
