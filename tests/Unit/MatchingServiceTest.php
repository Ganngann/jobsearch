<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\JobOffer;
use App\Models\Employer;
use App\Models\Skill;
use App\Services\MatchingService;
use App\Services\GeminiService;
use App\Services\VectorService;
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

        // MatchingService calculates score based on penalties and bonuses now.
        $this->assertIsArray($result);
        $this->assertEquals(100, $result['score']);
        
        $this->assertEquals(100, $result['details']['base']); // Base score

        // Find skill matched bonus
        $skillBonus = collect($result['details']['bonuses'])->firstWhere('type', 'skill_matched');
        $this->assertNotNull($skillBonus);
        $this->assertEquals(0, $skillBonus['value']); // 1 matched skill * 0 bonus = 0
        $this->assertEquals('Compétences maîtrisées (1)', $skillBonus['label']);
        $this->assertContains('Active Skill', $skillBonus['items']);

        // Check structure
        $this->assertArrayHasKey('penalties', $result['details']);
        $this->assertArrayHasKey('bonuses', $result['details']);
    }
}
