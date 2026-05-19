<?php

namespace Tests\Feature;

use App\Models\JobOffer;
use App\Models\Setting;
use App\Services\VectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class VectorWorkerCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_worker_exits_if_continuous_vectorization_disabled()
    {
        Setting::set('enable_continuous_vectorization', '0');

        $this->artisan('matching:vector-worker', ['--limit' => 1])
            ->expectsOutputToContain('La vectorisation continue est désactivée dans les paramètres. Arrêt.')
            ->assertExitCode(0);
    }

    public function test_worker_processes_job_offer_if_enabled()
    {
        Setting::set('enable_continuous_vectorization', '1');

        $jobOffer = JobOffer::factory()->create([
            'status' => 'active',
            'is_detailed' => true,
            'vector_embedding' => null,
            'published_at' => now(),
        ]);

        $mockVectorService = Mockery::mock(VectorService::class);
        $mockVectorService->shouldReceive('updateJobVector')
            ->once()
            ->with(Mockery::on(function ($arg) use ($jobOffer) {
                return $arg->id === $jobOffer->id;
            }))
            ->andReturn(true);

        $this->app->instance(VectorService::class, $mockVectorService);

        $this->artisan('matching:vector-worker', ['--limit' => 1, '--sleep' => 0])
            ->expectsOutputToContain('Vectorisation de #' . $jobOffer->forem_id)
            ->expectsOutputToContain('--> Succès')
            ->assertExitCode(0);

        $this->assertNotNull(Setting::get('heartbeat_vector-worker'));
    }

    public function test_worker_exits_if_no_job_offer_found()
    {
        Setting::set('enable_continuous_vectorization', '1');
        // Do not create any active/detailed job offers

        $this->artisan('matching:vector-worker', ['--limit' => 1, '--sleep' => 0])
            ->expectsOutputToContain('Aucune offre à vectoriser. Arrêt du worker (redémarrage prévu au prochain cycle).')
            ->assertExitCode(0);
    }
}
