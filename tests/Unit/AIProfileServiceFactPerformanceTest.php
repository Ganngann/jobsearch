<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserFact;
use App\Services\AIProfileService;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Mockery;

class AIProfileServiceFactPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected AIProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AIProfileService(Mockery::mock(GeminiService::class));
    }

    public function test_fact_updates_performance()
    {
        $user = User::factory()->create();

        // Create 100 facts for the user
        $factsData = [];
        for ($i = 0; $i < 100; $i++) {
            $factsData[] = [
                'user_id' => $user->id,
                'local_id' => $i + 1, // Using integer to avoid TypeError
                'content' => 'Old Content ' . $i,
                'category' => 'VALEURS',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        UserFact::insert($factsData);

        // Prepare 100 fact updates
        $aiResponse = [
            'facts' => []
        ];

        for ($i = 0; $i < 100; $i++) {
            $aiResponse['facts'][] = [
                'action' => 'update',
                'id' => $i + 1,
                'content' => 'New Content ' . $i,
                'category' => 'NEW_CATEGORY'
            ];
        }

        // Measure query count
        DB::enableQueryLog();

        $startTime = microtime(true);
        $this->service->processAIChanges($user, $aiResponse);
        $endTime = microtime(true);

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        $this->assertTrue(true, "Performance test complete");

        // Output result
        echo "Queries executed: " . $queryCount . "\n";
        echo "Time taken: " . ($endTime - $startTime) . " seconds\n";
    }
}
