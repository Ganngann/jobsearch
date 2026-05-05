<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Skill;
use App\Services\AIProfileService;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class AIProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AIProfileService $service;
    protected $geminiMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->geminiMock = Mockery::mock(GeminiService::class);
        $this->service = new AIProfileService($this->geminiMock);
    }

    public function test_build_context_includes_only_validated_skills_with_correct_labels(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);

        $activeSkill = Skill::create(['label' => 'PHP Expert', 'code' => 'S1', 'type' => 'hard']);
        $draftSkill = Skill::create(['label' => 'Draft Skill', 'code' => 'S2', 'type' => 'hard']);
        $refusedSkill = Skill::create(['label' => 'Refused Skill', 'code' => 'S3', 'type' => 'hard']);

        $user->skills()->attach($activeSkill->id, ['status' => 'active', 'level' => 'advanced']);
        $user->skills()->attach($draftSkill->id, ['status' => 'draft', 'level' => 'beginner']);
        $user->skills()->attach($refusedSkill->id, ['status' => 'refused', 'level' => 'beginner']);

        // Access protected method buildContext via reflection
        $reflection = new \ReflectionClass(AIProfileService::class);
        $method = $reflection->getMethod('buildContext');
        $method->setAccessible(true);

        $contextJson = $method->invoke($this->service, $user);
        $context = json_decode($contextJson, true);

        $this->assertArrayHasKey('skills', $context);
        $this->assertCount(1, $context['skills']);
        $this->assertEquals('PHP Expert', $context['skills'][0]);
        $this->assertNotContains('Draft Skill', $context['skills']);
        $this->assertNotContains('Refused Skill', $context['skills']);
    }

    public function test_build_context_uses_label_not_name(): void
    {
        $user = User::factory()->create();
        $skill = Skill::create(['label' => 'Specialized Label', 'code' => 'S_SPEC', 'type' => 'hard']);
        $user->skills()->attach($skill->id, ['status' => 'active']);

        $reflection = new \ReflectionClass(AIProfileService::class);
        $method = $reflection->getMethod('buildContext');
        $method->setAccessible(true);

        $contextJson = $method->invoke($this->service, $user);
        $context = json_decode($contextJson, true);

        // This verifies that the 'label' column is used. 
        // If it used 'name' (which doesn't exist), it would be null.
        $this->assertEquals('Specialized Label', $context['skills'][0]);
    }
}
