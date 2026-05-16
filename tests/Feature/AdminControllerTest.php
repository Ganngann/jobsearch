<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

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
}
