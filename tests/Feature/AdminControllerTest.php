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
}
