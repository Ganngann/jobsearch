<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoveryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_displays_discovery()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/discovery');

        $response->assertStatus(200);
    }
}
