<?php

namespace Tests\Unit\Jobs;

use App\Jobs\TestQueueJob;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TestQueueJobTest extends TestCase
{
    public function test_it_logs_message()
    {
        Log::shouldReceive('info')
            ->once()
            ->with("QUEUE TEST: HELLO WORLD! LE WORKER FONCTIONNE.");

        $job = new TestQueueJob();
        $job->handle();

        $this->assertTrue(true);
    }
}
