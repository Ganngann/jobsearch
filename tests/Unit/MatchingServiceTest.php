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

        // Dans la nouvelle logique, on ne gagne des points que pour les compétences actives.
        // Les compétences "draft" de l'utilisateur ne comptent pas comme un match.
        
        $bonusSkillMatched = collect($result['details']['bonuses'])->firstWhere('type', 'skill_matched');

        $this->assertNotNull($bonusSkillMatched, "Un bonus pour compétences matchées devrait exister.");
        $this->assertCount(1, $bonusSkillMatched['items'], "Seule une compétence (active) devrait être matchée.");
        $this->assertEquals('Active Skill', $bonusSkillMatched['items'][0]);
    }
}
