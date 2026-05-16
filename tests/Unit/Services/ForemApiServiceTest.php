<?php

namespace Tests\Unit\Services;

use App\Services\ForemApiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ForemApiServiceTest extends TestCase
{
    protected ForemApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear caches
        Cache::flush();
        $this->service = new ForemApiService();
    }

    public function test_get_job_detail_success()
    {
        Http::fake([
            '*/Diffusion/DetailOffre/*' => Http::response([
                'id' => '123',
                'titreOffre' => 'Test Job'
            ], 200)
        ]);

        $result = $this->service->getJobDetail('123');
        $this->assertIsArray($result);
    }

    public function test_get_job_detail_returns_null_on_404()
    {
        Http::fake([
            '*/Diffusion/DetailOffre/*' => Http::response([], 404)
        ]);

        $result = $this->service->getJobDetail('123');
        $this->assertNull($result);
    }
}
