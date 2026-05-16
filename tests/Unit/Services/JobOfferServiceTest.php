<?php

namespace Tests\Unit\Services;

use App\Models\JobOffer;
use App\Models\Metier;
use App\Models\Employer;
use App\Models\Skill;
use App\Services\JobOfferService;
use App\Services\ForemApiService;
use App\Services\MatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;
use Illuminate\Support\Facades\DB;

class JobOfferServiceTest extends TestCase
{
    use RefreshDatabase;

    protected JobOfferService $service;
    protected $foremMock;
    protected $matchingMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->foremMock = Mockery::mock(ForemApiService::class);
        $this->matchingMock = Mockery::mock(MatchingService::class);
        $this->service = new JobOfferService($this->foremMock, $this->matchingMock);
    }

    public function test_sync_full_details_success()
    {
        $offer = JobOffer::factory()->create(['forem_id' => '123', 'is_detailed' => false]);

        $jobData = [
            'numero' => 'REF123',
            'nomEmployeur' => 'Test Corp',
            'metier' => 'Dev PHP',
            'title' => 'Super Dev',
            'lieuxTravail' => ['Bruxelles'],
            'competences' => [
                ['libelle' => 'PHP', 'required' => true]
            ]
        ];

        $this->foremMock->shouldReceive('getJobDetail')->with(123)->once()->andReturn($jobData);

        // Mock queue
        \Illuminate\Support\Facades\Queue::fake();

        $result = $this->service->syncFullDetails($offer);

        $this->assertTrue($result);
        $this->assertTrue($offer->fresh()->is_detailed);
    }
}
