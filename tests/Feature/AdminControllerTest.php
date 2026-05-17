<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\JobOffer;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that an admin can access the queue monitor page.
     */
    public function test_admin_can_access_queue_monitor(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/queue');

        $response->assertStatus(200);
        $response->assertViewIs('admin.queue');
    }

    /**
     * Test that a non-admin user cannot access the queue monitor page.
     */
    public function test_non_admin_cannot_access_queue_monitor(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $response = $this->actingAs($user)->get('/admin/queue');

        $response->assertStatus(403);
    }

    /**
     * Test that the dashboard correctly calculates jobs_pending_vectorization
     */
    public function test_dashboard_jobs_pending_vectorization_calculation(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        // Job 1: Active, detailed, no vector (SHOULD be counted)
        JobOffer::factory()->create([
            'status' => 'active',
            'is_detailed' => true,
            'vector_embedding' => null,
        ]);

        // Job 2: Active, NOT detailed, no vector (Should NOT be counted)
        JobOffer::factory()->create([
            'status' => 'active',
            'is_detailed' => false,
            'vector_embedding' => null,
        ]);

        // Job 3: Inactive, detailed, no vector (Should NOT be counted)
        JobOffer::factory()->create([
            'status' => 'inactive',
            'is_detailed' => true,
            'vector_embedding' => null,
        ]);

        // Job 4: Active, detailed, WITH vector (Should NOT be counted)
        JobOffer::factory()->create([
            'status' => 'active',
            'is_detailed' => true,
            'vector_embedding' => [0.1, 0.2, 0.3],
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');

        $stats = $response->viewData('stats');
        $this->assertEquals(1, $stats['jobs_pending_vectorization']);
    }

    /**
     * Test that an admin can clear the queue.
     */
    public function test_admin_can_clear_queue(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        \Illuminate\Support\Facades\DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{"job":"TestJob","data":[]}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => time(),
            'created_at' => time()
        ]);

        $this->assertEquals(1, \Illuminate\Support\Facades\DB::table('jobs')->count());

        $response = $this->actingAs($admin)->post(route('admin.queue.clear'));

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'File d\'attente purgée avec succès.');

        $this->assertEquals(0, \Illuminate\Support\Facades\DB::table('jobs')->count());
    }

    /**
     * Test that a non-admin cannot clear the queue.
     */
    public function test_non_admin_cannot_clear_queue(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $response = $this->actingAs($user)->post(route('admin.queue.clear'));

        $response->assertStatus(403);
    }

    /**
     * Test that an admin can clear failed jobs.
     */
    public function test_admin_can_clear_failed_jobs(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        \Illuminate\Support\Facades\DB::table('failed_jobs')->insert([
            'uuid' => 'test-uuid-123',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{"job":"TestFailedJob","data":[]}',
            'exception' => 'TestException: Error',
            'failed_at' => now(),
        ]);

        $this->assertEquals(1, \Illuminate\Support\Facades\DB::table('failed_jobs')->count());

        $response = $this->actingAs($admin)->post(route('admin.queue.failed.clear'));

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Historique des échecs purgé.');

        $this->assertEquals(0, \Illuminate\Support\Facades\DB::table('failed_jobs')->count());
    }

    /**
     * Test that a non-admin cannot clear failed jobs.
     */
    public function test_non_admin_cannot_clear_failed_jobs(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $response = $this->actingAs($user)->post(route('admin.queue.failed.clear'));

        $response->assertStatus(403);
    }

    /**
     * Test that an admin can update a user's global AI limit.
     */
    public function test_admin_can_update_global_ai_limit(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);
        $user = User::factory()->create([
            'daily_ai_limit' => 50,
        ]);

        $response = $this->actingAs($admin)
            ->from('/admin/dashboard')
            ->post(route('admin.users.update-limit', $user), [
                'limit' => 100,
            ]);

        $response->assertRedirect('/admin/dashboard');
        $response->assertSessionHas('success', "Limite IA globale de {$user->name} mise à jour.");
        $this->assertEquals(100, $user->fresh()->daily_ai_limit);
    }

    /**
     * Test that an admin can update a user's model-specific AI limit.
     */
    public function test_admin_can_update_model_specific_ai_limit(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);
        $user = User::factory()->create([
            'daily_ai_limits' => [],
        ]);

        $response = $this->actingAs($admin)
            ->from('/admin/dashboard')
            ->post(route('admin.users.update-limit', $user), [
                'limit' => 150,
                'model' => 'gemini-1.5-flash',
            ]);

        $response->assertRedirect('/admin/dashboard');
        $response->assertSessionHas('success', "Limite IA pour gemini-1.5-flash mise à jour pour {$user->name}.");
        $this->assertEquals(150, $user->fresh()->daily_ai_limits['gemini-1.5-flash']);
    }

    /**
     * Test that a non-admin user cannot update an AI limit.
     */
    public function test_non_admin_cannot_update_ai_limit(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);
        $targetUser = User::factory()->create([
            'daily_ai_limit' => 50,
        ]);

        $response = $this->actingAs($user)
            ->post(route('admin.users.update-limit', $targetUser), [
                'limit' => 100,
            ]);

        $response->assertStatus(403);
        $this->assertEquals(50, $targetUser->fresh()->daily_ai_limit);
    }

    /**
     * Test that the update limit endpoint validates input.
     */
    public function test_update_limit_validates_input(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);
        $user = User::factory()->create([
            'daily_ai_limit' => 50,
        ]);

        // Test with missing limit
        $response = $this->actingAs($admin)
            ->post(route('admin.users.update-limit', $user), []);

        $response->assertSessionHasErrors('limit');

        // Test with invalid limit type
        $response = $this->actingAs($admin)
            ->post(route('admin.users.update-limit', $user), [
                'limit' => 'invalid',
            ]);

        $response->assertSessionHasErrors('limit');

        // Test with negative limit
        $response = $this->actingAs($admin)
            ->post(route('admin.users.update-limit', $user), [
                'limit' => -10,
            ]);

        $response->assertSessionHasErrors('limit');

        // Ensure the database hasn't changed
        $this->assertEquals(50, $user->fresh()->daily_ai_limit);
    }

    /**
     * Test that an admin can access the settings page.
     */
    public function test_admin_can_access_settings_page(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.settings'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.settings');
    }

    /**
     * Test that a non-admin user cannot access the settings page.
     */
    public function test_non_admin_cannot_access_settings_page(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $response = $this->actingAs($user)->get(route('admin.settings'));

        $response->assertStatus(403);
    }

    /**
     * Test that the settings page shows only ai_pricing and ai_limits settings.
     */
    public function test_settings_page_shows_correct_settings(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        // Create some settings
        \App\Models\Setting::create(['key' => 'rate_in_model1', 'value' => '10', 'group' => 'ai_pricing']);
        \App\Models\Setting::create(['key' => 'limit_model2', 'value' => '100', 'group' => 'ai_limits']);
        \App\Models\Setting::create(['key' => 'some_other_setting', 'value' => '1', 'group' => 'other_group']);

        $response = $this->actingAs($admin)->get(route('admin.settings'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.settings');

        $settings = $response->viewData('settings');

        // Assert that the created settings are present
        $this->assertTrue($settings->contains('key', 'rate_in_model1'));
        $this->assertTrue($settings->contains('key', 'limit_model2'));

        // Assert that the other setting is not present
        $this->assertFalse($settings->contains('key', 'some_other_setting'));

        // Assert that all returned settings belong to the expected groups
        $this->assertTrue($settings->every(function ($setting) {
            return in_array($setting->group, ['ai_pricing', 'ai_limits']);
        }));
    }
}