<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\JobOffer;
use App\Models\Employer;
use App\Models\Skill;
use App\Services\MatchingService;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

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

        // Job has both skills
        $jobOffer->skills()->attach([$activeSkill->id, $draftSkill->id]);

        $result = $this->service->calculatePreScore($user, $jobOffer);

        // MatchingService gives 40% to skills.
        // If it matches 1 out of 2 => score should be (1/2)*40 = 20.
        // If it matches 2 out of 2 => score should be (2/2)*40 = 40.
        
        $this->assertEquals(20, $result['details']['categories']['skills']['score']);
        $this->assertCount(1, $result['details']['categories']['skills']['missing']);
        $this->assertEquals('Draft Skill', $result['details']['categories']['skills']['missing'][0]);
    }
}
