<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\ProfileMessage;
use App\Models\ProfileSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\AIProfileService;
use Mockery;
use Mockery\MockInterface;

class ProfileChatControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_displays_chat_interface()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->instance(
            AIProfileService::class,
            Mockery::mock(AIProfileService::class, function (MockInterface $mock) {
                // If it is called, we don't care, but we need to allow it
                $mock->shouldReceive('generateOpeningMessage')->andReturn([
                    'message' => 'Hello',
                    'suggestions' => [],
                ]);
                $mock->shouldReceive('processAIChanges')->andReturnNull();
            })->shouldIgnoreMissing()
        );

        $response = $this->get('/profile/builder');

        $response->assertStatus(200);
    }

    public function test_new_session_creates_session_and_redirects()
    {
        $user = User::factory()->create();

        $initialSessionId = 'old-session-id';

        $this->actingAs($user)
             ->withSession(['profile_builder_session' => $initialSessionId]);

        $response = $this->get('/profile/builder/reset');

        $response->assertRedirect(route('profile.builder'));
        $response->assertSessionHas('profile_builder_session');

        $newSessionId = session('profile_builder_session');
        $this->assertNotEquals($initialSessionId, $newSessionId);
        $this->assertNotEmpty($newSessionId);
    }

    public function test_chat_sends_message_and_returns_response()
    {
        $user = User::factory()->create();
        $session = ProfileSession::create(['id' => uniqid(), 'user_id' => $user->id, 'title' => 'Test']);

        $this->instance(
            AIProfileService::class,
            Mockery::mock(AIProfileService::class, function (MockInterface $mock) {
                $mock->shouldReceive('chat')->once()->andReturn([
                    'reply' => 'Hello',
                    'suggestions' => [],
                    'changes' => null
                ]);
                $mock->shouldReceive('processAIChanges')->andReturnNull();
            })
        );

        $this->actingAs($user)
             ->withSession(['profile_builder_session' => $session->id]);

        $response = $this->postJson('/profile/builder/message', [
            'message' => 'Hello AI',
            'session_id' => $session->id
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['reply' => 'Hello']);

        $this->assertDatabaseHas('profile_messages', [
            'user_id' => $user->id,
            'content' => 'Hello AI',
            'role' => 'user'
        ]);
    }

    public function test_upload_document()
    {
        $user = User::factory()->create();
        $session = ProfileSession::create(['id' => uniqid(), 'user_id' => $user->id, 'title' => 'Test']);

        $this->actingAs($user)
             ->withSession(['profile_builder_session' => $session->id]);

        $this->instance(
            \App\Services\ResumeParserService::class,
            Mockery::mock(\App\Services\ResumeParserService::class, function (MockInterface $mock) {
                $mock->shouldReceive('extractText')->once()->andReturn('CV Content');
            })
        );

        $this->instance(
            AIProfileService::class,
            Mockery::mock(AIProfileService::class, function (MockInterface $mock) {
                $mock->shouldReceive('chat')->once()->andReturn([
                    'reply' => 'CV uploaded',
                    'suggestions' => [],
                    'changes' => null
                ]);
                $mock->shouldReceive('processAIChanges')->andReturnNull();
            })
        );

        $file = \Illuminate\Http\UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf');

        $response = $this->postJson('/profile/builder/upload', [
            'document' => $file
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['reply' => 'CV uploaded']);
    }
}
