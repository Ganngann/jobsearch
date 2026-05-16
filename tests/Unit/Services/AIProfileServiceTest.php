<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\Skill;
use App\Services\AIProfileService;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;
use Mockery\MockInterface;

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

        $reflection = new \ReflectionClass(AIProfileService::class);
        $method = $reflection->getMethod('buildContext');
        $method->setAccessible(true);

        $contextJson = $method->invoke($this->service, $user);
        $context = json_decode($contextJson, true);

        $this->assertArrayHasKey('skills', $context);
        $this->assertCount(1, $context['skills']);
        $this->assertEquals('PHP Expert', $context['skills'][0]);
    }

    public function test_generate_opening_message()
    {
        $user = User::factory()->create(['name' => 'John Doe']);

        $this->geminiMock->shouldReceive('forUser')->with($user)->andReturn($this->geminiMock);
        $this->geminiMock->shouldReceive('withModel')->andReturn($this->geminiMock);

        $this->geminiMock->shouldReceive('chat')->andReturn([
            'reply' => 'Hello John!',
            'suggestions' => ['Can you add skills?']
        ]);
        $this->geminiMock->shouldReceive('log')->andReturnNull();

        $result = $this->service->generateOpeningMessage($user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('reply', $result);
    }

    public function test_process_ai_changes_adds_fact()
    {
        $user = User::factory()->create();

        $response = [
            'facts' => [
                ['action' => 'add', 'content' => 'Test fact', 'category' => 'Test']
            ],
            'skills' => [
                ['action' => 'add', 'label' => 'Laravel']
            ],
            'experiences' => [
                ['action' => 'add', 'company' => 'Google', 'title' => 'Dev']
            ],
            'educations' => [
                ['action' => 'add', 'school' => 'MIT']
            ]
        ];

        $this->service->processAIChanges($user, $response);

        $this->assertDatabaseHas('user_facts', [
            'user_id' => $user->id,
            'content' => 'Test fact',
            'proposed_action' => 'add'
        ]);

        $this->assertDatabaseHas('experiences', [
            'user_id' => $user->id,
            'company' => 'Google',
            'proposed_action' => 'add'
        ]);
    }
}
