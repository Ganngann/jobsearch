<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Skill;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertStatus(200);
    }

    public function test_edit_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/edit');

        $response->assertStatus(200);
    }

    public function test_update_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'New Name',
            'email' => 'new@example.com'
        ]);

        $response->assertRedirect('/profile/edit');
        $this->assertEquals('New Name', $user->fresh()->name);
    }

    public function test_search_skills()
    {
        $user = User::factory()->create();
        \App\Models\Skill::factory()->create(['label' => 'PHP']);

        $response = $this->actingAs($user)->get('/api/skills/search?q=PH');

        $response->assertStatus(200);
    }

    public function test_search_metiers()
    {
        $user = User::factory()->create();
        \App\Models\Metier::create(['label' => 'Developer', 'code' => 'D123']);

        $response = $this->actingAs($user)->get('/api/metiers/search?q=Dev');

        $response->assertStatus(200);
    }

    public function test_delete_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/profile', [
            'password' => 'password' // Assuming password is 'password' by factory
        ]);

        $response->assertRedirect('/');
        $this->assertNull(User::find($user->id));
    }
}
