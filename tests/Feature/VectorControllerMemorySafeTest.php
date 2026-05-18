<?php

namespace Tests\Feature;

use App\Models\JobOffer;
use App\Models\User;
use App\Models\UserMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class VectorControllerMemorySafeTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_similarities_uses_memory_safe_chunking()
    {
        $user = User::factory()->create([
            'vector_embedding' => array_fill(0, 768, 0.1)
        ]);

        $this->actingAs($user);

        // Crée des offres (actives + détaillées) pour déclencher le calcul
        JobOffer::factory()->count(10)->create([
            'status' => 'active',
            'is_detailed' => true,
            'vector_embedding' => array_fill(0, 768, 0.2),
            'raw_data' => array_fill(0, 100, 'large data payload'), // Données lourdes pour prouver que le test passe
        ]);

        $response = $this->postJson(route('matching.vector-sync'));

        $response->assertStatus(200);
        $response->assertJsonPath('count', 10);
        $this->assertEquals(10, UserMatch::where('user_id', $user->id)->count());
    }

    public function test_launch_batch_vectorization_uses_memory_safe_chunking()
    {
        Queue::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        // Crée des offres sans vecteur, actives et détaillées
        JobOffer::factory()->count(5)->create([
            'status' => 'active',
            'is_detailed' => true,
            'vector_embedding' => null,
            'raw_data' => array_fill(0, 100, 'large data payload'),
        ]);

        // Crée des offres déjà vectorisées (ne doivent pas être dispatchées)
        JobOffer::factory()->count(3)->create([
            'status' => 'active',
            'is_detailed' => true,
            'vector_embedding' => array_fill(0, 768, 0.1),
        ]);

        $response = $this->post(route('admin.matching.scan'));

        $response->assertRedirect();
        $response->assertSessionHas('success', '5 offres envoyées en file d\'attente pour vectorisation.');

        Queue::assertPushed(\App\Jobs\VectorizeJobOffer::class, 5);
    }
}
