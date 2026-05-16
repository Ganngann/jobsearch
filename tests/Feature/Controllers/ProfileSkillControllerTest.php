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
}
