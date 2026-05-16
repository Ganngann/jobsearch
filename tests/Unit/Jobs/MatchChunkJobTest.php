<?php

namespace Tests\Unit\Jobs;

use App\Jobs\MatchChunkJob;
use App\Models\User;
use App\Models\JobOffer;
use App\Models\ZipCode;
use App\Services\MatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Mockery;

class MatchChunkJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_matches_offers_in_chunk()
    {
        $user = User::factory()->create(['zip_code' => '1000']);
        $zip = new ZipCode();
        $zip->zip_code = '1000';
        $zip->city = 'Bruxelles';
        $zip->latitude = 50.8503;
        $zip->longitude = 4.3517;
        $zip->save();

        $offer1 = JobOffer::factory()->create();
        $offer2 = JobOffer::factory()->create();
        $jobOfferIds = [$offer1->id, $offer2->id];

        $matchingServiceMock = Mockery::mock(MatchingService::class);
        $matchingServiceMock->shouldReceive('match')
                            ->twice()
                            ->withArgs(function ($u, $o, $forceRecompute, $isMassSync, $context) use ($user, $offer1, $offer2) {
                                return $u->id === $user->id &&
                                       in_array($o->id, [$offer1->id, $offer2->id]) &&
                                       $forceRecompute === false &&
                                       $isMassSync === false &&
                                       is_array($context) &&
                                       array_key_exists('skill_ids', $context);
                            });

        $job = new MatchChunkJob($user, $jobOfferIds);

        $this->assertEquals($user->id . '_' . md5(json_encode($jobOfferIds)), $job->uniqueId());
        $this->assertEquals('low', $job->queue);

        $job->handle($matchingServiceMock);

        $this->assertTrue(true);
    }
}
