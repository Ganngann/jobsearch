<?php

namespace Tests\Unit\Jobs;

use App\Jobs\VectorizeJobOffer;
use App\Models\JobOffer;
use App\Services\VectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class VectorizeJobOfferTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_does_not_vectorize_if_already_has_embedding()
    {
        $jobOffer = JobOffer::factory()->create(['vector_embedding' => '[0.1]']);

        $vectorServiceMock = Mockery::mock(VectorService::class);
        $vectorServiceMock->shouldNotReceive('updateJobVector');

        $job = new VectorizeJobOffer($jobOffer);
        $job->handle($vectorServiceMock);

        $this->assertEquals((string)$jobOffer->id, $job->uniqueId());

        $this->assertTrue(true);
    }

    public function test_it_vectorizes_job_offer()
    {
        Log::shouldReceive('info')->once();

        $jobOffer = JobOffer::factory()->create(['vector_embedding' => null]);

        $vectorServiceMock = Mockery::mock(VectorService::class);
        $vectorServiceMock->shouldReceive('updateJobVector')
                          ->once()
                          ->with(Mockery::on(function ($arg) use ($jobOffer) {
                              return $arg->id === $jobOffer->id;
                          }));

        $job = new VectorizeJobOffer($jobOffer);
        $job->handle($vectorServiceMock);

        $this->assertTrue(true);
    }

    public function test_it_handles_exceptions()
    {
        Log::shouldReceive('error')->once();

        $jobOffer = JobOffer::factory()->create(['vector_embedding' => null]);

        $vectorServiceMock = Mockery::mock(VectorService::class);
        $vectorServiceMock->shouldReceive('updateJobVector')
                          ->once()
                          ->andThrow(new \Exception('Test error'));

        $job = new VectorizeJobOffer($jobOffer);
        $job->handle($vectorServiceMock);

        $this->assertTrue(true);
    }
}
