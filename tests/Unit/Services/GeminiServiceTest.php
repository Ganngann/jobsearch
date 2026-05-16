<?php

namespace Tests\Unit\Services;

use App\Services\GeminiService;
use App\Models\AiLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeminiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GeminiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gemini.key' => 'fake_key']);
        $this->service = new GeminiService();
    }

    public function test_ask_success()
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'AI Response']
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->ask('Hello');
        $this->assertEquals('AI Response', $result);
    }

    public function test_generate_json_success()
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '{"key": "value"}']
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->generateJson('Hello');
        $this->assertIsArray($result);
        $this->assertEquals('value', $result['key']);
    }
}
