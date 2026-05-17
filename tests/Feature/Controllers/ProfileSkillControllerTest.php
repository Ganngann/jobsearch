<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\Skill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSkillControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_displays_skills()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/profile/skills');

        $response->assertStatus(200);
    }

    public function test_update_status()
    {
        $user = User::factory()->create();
        $skill = Skill::factory()->create();
        $user->skills()->attach($skill->id, ['status' => 'draft']);

        $this->actingAs($user);

        $response = $this->postJson("/profile/skills/{$skill->id}/status", [
            'status' => 'active'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_skill', [
            'user_id' => $user->id,
            'skill_id' => $skill->id,
            'status' => 'active'
        ]);
    }

    public function test_soft_skills_returns_unassociated_skills()
    {
        $user = User::factory()->create();

        $skill1 = Skill::factory()->create(['type' => 'soft', 'label' => 'Skill 1']);
        $skill2 = Skill::factory()->create(['type' => 'soft', 'label' => 'Skill 2']);
        $skill3 = Skill::factory()->create(['type' => 'hard', 'label' => 'Skill 3']);

        $user->skills()->attach($skill1->id, ['status' => 'active']);

        $this->actingAs($user);

        $response = $this->get('/profile/skills/soft');

        $response->assertStatus(200);

        $response->assertJsonPath('suggestions.0.id', $skill2->id);
        $response->assertJsonMissing(['id' => $skill1->id]);
        $response->assertJsonMissing(['id' => $skill3->id]);
    }

    public function test_suggest_returns_empty_array()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->postJson('/profile/skills/suggest');

        $response->assertStatus(200);

        $response->assertJson([
            'suggestions' => []
        ]);
    }
}
