<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserModelLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_global_limit_if_no_model_specified()
    {
        $user = User::factory()->create([
            'daily_ai_limit' => 5,
            'daily_ai_usage' => 4
        ]);

        $this->assertTrue($user->useAiPoint());
        $this->assertEquals(5, $user->daily_ai_usage);
        $this->assertFalse($user->useAiPoint());
    }

    public function test_it_uses_model_specific_limit_if_set()
    {
        $user = User::factory()->create([
            'daily_ai_limit' => 100, // Large global limit
            'daily_ai_limits' => ['expensive-model' => 2]
        ]);

        $this->assertTrue($user->useAiPoint('expensive-model')); // Usage 1
        $this->assertTrue($user->useAiPoint('expensive-model')); // Usage 2
        $this->assertFalse($user->useAiPoint('expensive-model')); // Usage 3 -> Fail
    }

    public function test_it_falls_back_to_global_setting_for_model()
    {
        Setting::set('limit_cheap-model', 3);
        
        $user = User::factory()->create([
            'daily_ai_limit' => 0 // Global limit 0 but model limit 3 should work
        ]);

        $this->assertTrue($user->useAiPoint('cheap-model'));
        $this->assertTrue($user->useAiPoint('cheap-model'));
        $this->assertTrue($user->useAiPoint('cheap-model'));
        $this->assertFalse($user->useAiPoint('cheap-model'));
    }

    public function test_daily_reset_clears_breakdown()
    {
        $user = User::factory()->create([
            'daily_ai_limit' => 10,
            'daily_ai_usage' => 10,
            'daily_ai_usage_breakdown' => ['model-a' => 10],
            'last_ai_usage_at' => now()->subDay()
        ]);

        // La consommation devrait réussir car le compteur doit être reset
        $this->assertTrue($user->useAiPoint('model-a'));
        $this->assertEquals(1, $user->daily_ai_usage);
        $this->assertEquals(1, $user->daily_ai_usage_breakdown['model-a']);
    }
}
