<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\UserFeedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_submit_feedback(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('feedback.store'), [
            'message' => 'This is a great app!',
            'type' => 'feedback',
            'page_url' => 'https://example.com/page',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Merci pour ton retour !',
                 ]);

        $this->assertDatabaseHas('user_feedback', [
            'user_id' => $user->id,
            'message' => 'This is a great app!',
            'type' => 'feedback',
            'page_url' => 'https://example.com/page',
        ]);
    }

    public function test_unauthenticated_user_can_submit_feedback(): void
    {
        $response = $this->postJson(route('feedback.store'), [
            'message' => 'Found a bug on login page.',
            'type' => 'bug',
            'page_url' => '/login',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Merci pour ton retour !',
                 ]);

        $this->assertDatabaseHas('user_feedback', [
            'user_id' => null,
            'message' => 'Found a bug on login page.',
            'type' => 'bug',
            'page_url' => '/login',
        ]);
    }

    public function test_feedback_validation_errors(): void
    {
        $response = $this->postJson(route('feedback.store'), []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['message', 'type']);

        $response = $this->postJson(route('feedback.store'), [
            'message' => 'sho', // less than 5 chars
            'type' => 'invalid_type',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['message', 'type']);

        $this->assertDatabaseCount('user_feedback', 0);
    }
}
