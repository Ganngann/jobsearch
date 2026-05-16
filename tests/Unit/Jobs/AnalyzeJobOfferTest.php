<?php

namespace Tests\Unit\Jobs;

use App\Jobs\AnalyzeJobOffer;
use App\Models\User;
use App\Models\JobOffer;
use App\Models\UserMatch;
use App\Services\MatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class AnalyzeJobOfferTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_analyzes_job_offer_successfully()
    {
        Log::shouldReceive('info')->twice();

        $user = User::factory()->create();
        $jobOffer = JobOffer::factory()->create();
        $match = new UserMatch();
        $match->user_id = $user->id;
        $match->job_offer_id = $jobOffer->id;
        $match->final_score = 50;
        $match->save();

        $matchingServiceMock = Mockery::mock(MatchingService::class);
        $matchingServiceMock->shouldReceive('performAiAnalysis')
                            ->once()
                            ->with($user, $jobOffer, $match)
                            ->andReturn(true);

        $job = new AnalyzeJobOffer($user, $jobOffer, $match);

        $this->assertEquals($user->id . '_' . $jobOffer->id, $job->uniqueId());

        $job->handle($matchingServiceMock);

        $match->refresh();
        $this->assertEquals('completed', $match->ai_status);
    }

    public function test_it_handles_failed_analysis()
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('warning')->once();

        $user = User::factory()->create();
        $jobOffer = JobOffer::factory()->create();
        $match = new UserMatch();
        $match->user_id = $user->id;
        $match->job_offer_id = $jobOffer->id;
        $match->final_score = 50;
        $match->save();

        $matchingServiceMock = Mockery::mock(MatchingService::class);
        $matchingServiceMock->shouldReceive('performAiAnalysis')
                            ->once()
                            ->with($user, $jobOffer, $match)
                            ->andReturn(false);

        $job = new AnalyzeJobOffer($user, $jobOffer, $match);
        $job->handle($matchingServiceMock);

        $match->refresh();
        $this->assertEquals('failed', $match->ai_status);
    }

    public function test_it_handles_exceptions()
    {
        Log::shouldReceive('info')->once();

        $user = User::factory()->create();
        $jobOffer = JobOffer::factory()->create();
        $match = new UserMatch();
        $match->user_id = $user->id;
        $match->job_offer_id = $jobOffer->id;
        $match->final_score = 50;
        $match->save();

        $matchingServiceMock = Mockery::mock(MatchingService::class);
        $matchingServiceMock->shouldReceive('performAiAnalysis')
                            ->once()
                            ->with($user, $jobOffer, $match)
                            ->andThrow(new \Exception('Test Error'));

        $job = new AnalyzeJobOffer($user, $jobOffer, $match);

        $this->expectException(\Exception::class);

        try {
            $job->handle($matchingServiceMock);
        } finally {
            $match->refresh();
            $this->assertEquals('failed', $match->ai_status);
        }
    }
}
