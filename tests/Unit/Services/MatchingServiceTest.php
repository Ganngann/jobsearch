<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\JobOffer;
use App\Models\Employer;
use App\Models\Skill;
use App\Models\UserMatch;
use App\Services\MatchingService;
use App\Services\GeminiService;
use App\Services\VectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;
use Illuminate\Support\Facades\Queue;
use App\Jobs\MatchUserJob;
use App\Jobs\MatchChunkJob;

class MatchingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MatchingService $service;
    protected $geminiMock;
    protected $vectorServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->geminiMock = Mockery::mock(GeminiService::class);
        $this->vectorServiceMock = Mockery::mock(\App\Services\VectorService::class);
        $this->service = new MatchingService($this->geminiMock, $this->vectorServiceMock);
    }

    public function test_calculate_pre_score_only_considers_active_skills(): void
    {
        $user = User::factory()->create();
        $employer = Employer::create(['label' => 'Test Employer']);

        $activeSkill = Skill::create(['label' => 'Active Skill', 'code' => 'S1', 'type' => 'hard']);
        $draftSkill = Skill::create(['label' => 'Draft Skill', 'code' => 'S2', 'type' => 'hard']);

        // Le candidat a une compétence active et une compétence draft
        $user->skills()->attach($activeSkill->id, ['status' => 'active', 'level' => 'expert']);
        $user->skills()->attach($draftSkill->id, ['status' => 'draft', 'level' => 'expert']);

        $jobOffer = JobOffer::create([
            'forem_id' => '222',
            'forem_ref' => 'REF222',
            'employer_id' => $employer->id,
            'contract_type' => 'CDI',
            'working_regime' => 'Temps plein',
            'title' => 'Test Job',
            'location' => '1000 Bruxelles',
            'is_detailed' => true,
        ]);

        // L'offre contient les deux compétences
        $jobOffer->skills()->attach([$activeSkill->id, $draftSkill->id]);

        $result = $this->service->calculatePreScore($user, $jobOffer);

        $this->assertIsArray($result);
        $this->assertEquals(100, $result['score']);
        $this->assertEquals(100, $result['details']['base']); // Base score
    }

    public function test_trigger_mass_match()
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->service->triggerMassMatch($user);

        Queue::assertPushed(MatchUserJob::class);
    }

    public function test_trigger_metier_match()
    {
        Queue::fake();
        $user = User::factory()->create();
        $metier = \App\Models\Metier::create(['label' => 'Test Metier', 'code' => 'TEST']);
        $employer = Employer::create(['label' => 'Test']);

        JobOffer::factory()->create([
            'metier_id' => $metier->id,
            'employer_id' => $employer->id,
            'is_detailed' => true,
            'status' => 'active'
        ]);

        $this->service->triggerMetierMatch($user, $metier->id);

        Queue::assertPushed(MatchChunkJob::class);
    }
}
