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

    public function test_save_basic_offer_success()
    {
        $offerData = [
            'id' => 'forem-123',
            'numero' => 'REF123',
            'titre' => 'Super Dev',
            'nomEmployeur' => 'Test Corp',
            'lieuxTravail' => ['Bruxelles'],
            'typeContrat' => 'CDI',
            'regimeTravail' => 'Temps plein',
            'nombrePostes' => 2,
            'email' => 'contact@test.com',
            'isPostulable' => true,
            'debut' => '2024-01-01',
            'fin' => '2024-12-31',
            'secteursActivite' => ['Informatique', 'Web'],
        ];

        $jobOffer = $this->service->saveBasicOffer($offerData);

        // Assert Employer
        $this->assertDatabaseHas('employers', [
            'label' => 'Test Corp',
        ]);

        $employer = Employer::where('label', 'Test Corp')->first();

        // Assert JobOffer
        $this->assertDatabaseHas('job_offers', [
            'forem_id' => 'forem-123',
            'forem_ref' => 'REF123',
            'title' => 'Super Dev',
            'employer_id' => $employer->id,
            'location' => 'Bruxelles',
            'locations_json' => json_encode(['Bruxelles']),
            'contract_type' => 'CDI',
            'working_regime' => 'Temps plein',
            'nombre_postes' => 2,
            'contact_email' => 'contact@test.com',
            'is_postulable' => 1,
            'status' => 'active',
            'is_detailed' => 0, // false by default
        ]);

        $this->assertEquals('2024-01-01 00:00:00', $jobOffer->start_date->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-12-31 00:00:00', $jobOffer->expires_at->format('Y-m-d H:i:s'));

        // Assert Sectors
        $this->assertDatabaseHas('sectors', ['label' => 'Informatique']);
        $this->assertDatabaseHas('sectors', ['label' => 'Web']);
        $this->assertCount(2, $jobOffer->sectors);
        $this->assertTrue($jobOffer->sectors->contains('label', 'Informatique'));
        $this->assertTrue($jobOffer->sectors->contains('label', 'Web'));
    }

    public function test_save_basic_offer_smart_refresh()
    {
        // Create an existing offer that is detailed, with a specific expires_at date
        $existingOffer = JobOffer::factory()->create([
            'forem_id' => 'forem-123',
            'is_detailed' => true,
            'expires_at' => \Carbon\Carbon::parse('2024-06-01'),
        ]);

        $offerData = [
            'id' => 'forem-123',
            'numero' => 'REF123', // Add missing required field
            'nomEmployeur' => 'Test Corp', // Add missing required field
            'titre' => 'Super Dev Updated',
            // Provide a new expires_at date
            'fin' => '2024-12-31',
            'lieuxTravail' => [],
        ];

        $updatedOffer = $this->service->saveBasicOffer($offerData);

        // Assert the offer is no longer detailed
        $this->assertFalse((bool)$updatedOffer->is_detailed);
        // Assert the new expires_at date was applied
        $this->assertEquals('2024-12-31 00:00:00', $updatedOffer->expires_at->format('Y-m-d H:i:s'));
        // Assert title is updated
        $this->assertEquals('Super Dev Updated', $updatedOffer->title);
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
