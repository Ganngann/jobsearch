<?php

namespace Tests\Unit\Jobs;

use App\Jobs\RecalculateMatchesJob;
use App\Models\User;
use App\Services\MatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class RecalculateMatchesJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_triggers_mass_match_for_user()
    {
        Log::shouldReceive('info')->twice();

        $user = User::factory()->create();

        $matchingServiceMock = Mockery::mock(MatchingService::class);
        $matchingServiceMock->shouldReceive('triggerMassMatch')
                            ->once()
                            ->with(Mockery::on(function ($arg) use ($user) {
                                return $arg->id === $user->id;
                            }));

        $job = new RecalculateMatchesJob($user);

        $this->assertEquals((string)$user->id, $job->uniqueId());

        $job->handle($matchingServiceMock);

        $this->assertTrue(true);
    }
}
