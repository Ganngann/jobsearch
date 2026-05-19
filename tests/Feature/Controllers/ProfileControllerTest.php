<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use App\Models\Skill;
use Mockery;
use App\Services\ResumeParserService;

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

    public function test_search_skills_escapes_wildcards()
    {
        $user = User::factory()->create();
        \App\Models\Skill::factory()->create(['label' => '100% Cotton']);
        \App\Models\Skill::factory()->create(['label' => '1000 items']);

        $response = $this->actingAs($user)->get('/api/skills/search?q=100%25'); // URL encoded %

        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
        $this->assertEquals('100% Cotton', $response->json()[0]['label']);
    }

    public function test_search_metiers()
    {
        $user = User::factory()->create();
        \App\Models\Metier::create(['label' => 'Developer', 'code' => 'D123']);

        $response = $this->actingAs($user)->get('/api/metiers/search?q=Dev');

        $response->assertStatus(200);
    }

    public function test_search_metiers_escapes_wildcards()
    {
        $user = User::factory()->create();
        \App\Models\Metier::create(['label' => 'Front_End', 'code' => 'D124']);
        \App\Models\Metier::create(['label' => 'Front End', 'code' => 'D125']);

        $response = $this->actingAs($user)->get('/api/metiers/search?q=Front_');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
        $this->assertEquals('Front_End', $response->json()[0]['label']);
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

    public function test_upload_resume_error_handling_json()
    {
        $user = User::factory()->create();

        $mockParser = Mockery::mock(ResumeParserService::class);
        $mockParser->shouldReceive('extractText')
            ->once()
            ->andThrow(new \Exception('Parsing failed due to encryption'));

        $this->instance(ResumeParserService::class, $mockParser);

        $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/profile/upload-resume', [
                'resume' => $file
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Parsing failed due to encryption'
            ]);
    }

    public function test_upload_resume_error_handling_redirect()
    {
        $user = User::factory()->create();

        $mockParser = Mockery::mock(ResumeParserService::class);
        $mockParser->shouldReceive('extractText')
            ->once()
            ->andThrow(new \Exception('Parsing failed due to encryption'));

        $this->instance(ResumeParserService::class, $mockParser);

        $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)
            ->from('/profile/edit')
            ->post('/profile/upload-resume', [
                'resume' => $file
            ]);

        $response->assertRedirect('/profile/edit')
            ->assertSessionHasErrors([
                'resume' => 'Parsing failed due to encryption'
            ]);
    }
}
