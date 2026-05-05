<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\JobOffer;
use App\Models\Employer;
use App\Models\Skill;
use App\Services\ProfileMappingService;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;
use Illuminate\Support\Facades\DB;

class ProfileMappingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProfileMappingService $service;
    protected $geminiMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->geminiMock = Mockery::mock(GeminiService::class);
        $this->service = new ProfileMappingService($this->geminiMock);
    }

    public function test_suggest_skills_filters_catalog_by_at_least_two_active_offers(): void
    {
        $user = User::factory()->create();
        $employer = Employer::create(['label' => 'Test Employer']);

        // S1: 0 offers -> Should NOT be in catalog
        $s1 = Skill::factory()->create(['label' => 'Skill 0']);

        // S2: 1 active offer -> Should NOT be in catalog
        $s2 = Skill::factory()->create(['label' => 'Skill 1']);
        $this->createJobOffer($employer, 'active', [$s2->id]);

        // S3: 2 active offers -> Should BE in catalog
        $s3 = Skill::factory()->create(['label' => 'Skill 2']);
        $this->createJobOffer($employer, 'active', [$s3->id]);
        $this->createJobOffer($employer, 'active', [$s3->id]);

        // S4: 2 expired offers -> Should NOT be in catalog
        $s4 = Skill::factory()->create(['label' => 'Skill Expired']);
        $this->createJobOffer($employer, 'expired', [$s4->id]);
        $this->createJobOffer($employer, 'expired', [$s4->id]);

        // S5: 1 active + 1 expired -> Should NOT be in catalog (needs 2 ACTIVE)
        $s5 = Skill::factory()->create(['label' => 'Skill Mixed']);
        $this->createJobOffer($employer, 'active', [$s5->id]);
        $this->createJobOffer($employer, 'expired', [$s5->id]);

        // Mock Gemini response
        $this->geminiMock->shouldReceive('withModel')
            ->with('gemini-2.5-flash-lite')
            ->andReturn($this->geminiMock);

        $this->geminiMock->shouldReceive('generateJson')
            ->once()
            ->withArgs(function($prompt) use ($s1, $s2, $s3, $s4, $s5) {
                // The prompt MUST contain S3 but NOT others
                return str_contains($prompt, "[ID:{$s3->id}]") 
                    && !str_contains($prompt, "[ID:{$s1->id}]")
                    && !str_contains($prompt, "[ID:{$s2->id}]")
                    && !str_contains($prompt, "[ID:{$s4->id}]")
                    && !str_contains($prompt, "[ID:{$s5->id}]");
            })
            ->andReturn([
                'suggestions' => [
                    ['id' => $s3->id, 'reason' => 'Test reason']
                ]
            ]);

        $results = $this->service->suggestSkills($user);

        $this->assertCount(1, $results);
        $this->assertEquals($s3->id, $results[0]['id']);
        $this->assertEquals(2, $results[0]['popularity']); // Popularity should count active offers (2)
    }

    public function test_suggest_skills_popularity_counts_only_active_offers(): void
    {
        $user = User::factory()->create();
        $employer = Employer::create(['label' => 'Test Employer']);

        $skill = Skill::factory()->create(['label' => 'Popular Skill']);
        
        // 2 active, 1 expired
        $this->createJobOffer($employer, 'active', [$skill->id]);
        $this->createJobOffer($employer, 'active', [$skill->id]);
        $this->createJobOffer($employer, 'expired', [$skill->id]);

        $this->geminiMock->shouldReceive('withModel')->andReturn($this->geminiMock);
        $this->geminiMock->shouldReceive('generateJson')->andReturn([
            'suggestions' => [['id' => $skill->id, 'reason' => 'foo']]
        ]);

        $results = $this->service->suggestSkills($user);

        $this->assertEquals(2, $results[0]['popularity']); // Not 3
    }

    public function test_suggest_skills_excludes_known_skills(): void
    {
        $user = User::factory()->create();
        $employer = Employer::create(['label' => 'Test Employer']);

        $knownSkill = Skill::factory()->create(['label' => 'Known']);
        $newSkill = Skill::factory()->create(['label' => 'New']);

        // Both have 2 active offers
        $this->createJobOffer($employer, 'active', [$knownSkill->id]);
        $this->createJobOffer($employer, 'active', [$knownSkill->id]);
        $this->createJobOffer($employer, 'active', [$newSkill->id]);
        $this->createJobOffer($employer, 'active', [$newSkill->id]);

        // Attach known skill to user
        $user->skills()->attach($knownSkill->id, ['status' => 'active', 'level' => 'expert']);

        $this->geminiMock->shouldReceive('withModel')->andReturn($this->geminiMock);
        $this->geminiMock->shouldReceive('generateJson')
            ->once()
            ->withArgs(function($prompt) use ($knownSkill, $newSkill) {
                return str_contains($prompt, "[ID:{$newSkill->id}]") 
                    && !str_contains($prompt, "[ID:{$knownSkill->id}]");
            })
            ->andReturn(['suggestions' => []]);

        $this->service->suggestSkills($user);
    }

    public function test_suggest_skills_marks_unselected_skills_as_neutral(): void
    {
        $user = User::factory()->create();
        $employer = Employer::create(['label' => 'Test Employer']);

        $sSelected = Skill::factory()->create(['label' => 'Selected']);
        $this->createJobOffer($employer, 'active', [$sSelected->id]);
        $this->createJobOffer($employer, 'active', [$sSelected->id]);

        $sRejected = Skill::factory()->create(['label' => 'Rejected']);
        $this->createJobOffer($employer, 'active', [$sRejected->id]);
        $this->createJobOffer($employer, 'active', [$sRejected->id]);

        $this->geminiMock->shouldReceive('withModel')->andReturn($this->geminiMock);
        $this->geminiMock->shouldReceive('generateJson')->andReturn([
            'suggestions' => [
                ['id' => $sSelected->id, 'reason' => 'match']
            ]
        ]);

        $this->service->suggestSkills($user);

        // Check user skills
        $this->assertDatabaseHas('user_skill', [
            'user_id' => $user->id,
            'skill_id' => $sRejected->id,
            'status' => 'neutral'
        ]);

        // Selected skill is NOT in user_skill yet because suggestSkills only RETURNS it, 
        // it doesn't attach it (the user must accept it via another controller)
        $this->assertDatabaseMissing('user_skill', [
            'user_id' => $user->id,
            'skill_id' => $sSelected->id
        ]);
    }

    public function test_suggest_skills_prompt_catalog_is_sorted_by_increasing_popularity(): void
    {
        $user = User::factory()->create();
        $employer = Employer::create(['label' => 'Test Employer']);

        $sLeast = Skill::factory()->create(['label' => 'Least Popular']);
        $this->createJobOffer($employer, 'active', [$sLeast->id]);
        $this->createJobOffer($employer, 'active', [$sLeast->id]); // 2 offers

        $sMost = Skill::factory()->create(['label' => 'Most Popular']);
        $this->createJobOffer($employer, 'active', [$sMost->id]);
        $this->createJobOffer($employer, 'active', [$sMost->id]);
        $this->createJobOffer($employer, 'active', [$sMost->id]);
        $this->createJobOffer($employer, 'active', [$sMost->id]); // 4 offers

        $this->geminiMock->shouldReceive('withModel')->andReturn($this->geminiMock);
        $this->geminiMock->shouldReceive('generateJson')
            ->once()
            ->withArgs(function($prompt) use ($sLeast, $sMost) {
                $posLeast = strpos($prompt, "[ID:{$sLeast->id}]");
                $posMost = strpos($prompt, "[ID:{$sMost->id}]");
                
                return $posLeast !== false && $posMost !== false && $posLeast < $posMost;
            })
            ->andReturn(['suggestions' => []]);

        $this->service->suggestSkills($user);
    }

    public function test_suggest_skills_are_sorted_by_increasing_popularity(): void
    {
        $user = User::factory()->create();
        $employer = Employer::create(['label' => 'Test Employer']);

        // S1: 2 active offers (Least popular)
        $s1 = Skill::factory()->create(['label' => 'Skill 2']);
        $this->createJobOffer($employer, 'active', [$s1->id]);
        $this->createJobOffer($employer, 'active', [$s1->id]);

        // S2: 4 active offers (Most popular)
        $s2 = Skill::factory()->create(['label' => 'Skill 4']);
        $this->createJobOffer($employer, 'active', [$s2->id]);
        $this->createJobOffer($employer, 'active', [$s2->id]);
        $this->createJobOffer($employer, 'active', [$s2->id]);
        $this->createJobOffer($employer, 'active', [$s2->id]);

        // S3: 3 active offers (Medium)
        $s3 = Skill::factory()->create(['label' => 'Skill 3']);
        $this->createJobOffer($employer, 'active', [$s3->id]);
        $this->createJobOffer($employer, 'active', [$s3->id]);
        $this->createJobOffer($employer, 'active', [$s3->id]);

        $this->geminiMock->shouldReceive('withModel')->andReturn($this->geminiMock);
        $this->geminiMock->shouldReceive('generateJson')->andReturn([
            'suggestions' => [
                ['id' => $s1->id, 'reason' => 'r1'],
                ['id' => $s2->id, 'reason' => 'r2'],
                ['id' => $s3->id, 'reason' => 'r3'],
            ]
        ]);

        $results = $this->service->suggestSkills($user);

        // Expected order: S1 (2), S3 (3), S2 (4)
        $this->assertCount(3, $results);
        $this->assertEquals($s1->id, $results[0]['id']);
        $this->assertEquals($s3->id, $results[1]['id']);
        $this->assertEquals($s2->id, $results[2]['id']);
    }

    private function createJobOffer($employer, $status, array $skillIds)
    {
        $job = JobOffer::factory()->create([
            'employer_id' => $employer->id,
            'status' => $status,
        ]);

        $job->skills()->attach($skillIds);
        return $job;
    }
}
