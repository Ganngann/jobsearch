<?php

namespace Tests\Unit\Jobs;

use App\Jobs\BatchMatchJobOffer;
use App\Models\JobOffer;
use App\Models\User;
use App\Models\Metier;
use App\Services\MatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class BatchMatchJobOfferTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_does_not_match_if_no_metier_id()
    {
        $jobOffer = JobOffer::factory()->create(['metier_id' => null]);

        $matchingServiceMock = Mockery::mock(MatchingService::class);
        $matchingServiceMock->shouldNotReceive('match');

        $job = new BatchMatchJobOffer($jobOffer);
        $job->handle($matchingServiceMock);

        $this->assertTrue(true);
    }
}
