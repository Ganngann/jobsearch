<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Metier;
use App\Models\Skill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_profile_mature_returns_false_when_elements_missing(): void
    {
        $user = User::factory()->create(['zip_code' => null]);

        $this->assertFalse($user->isProfileMature());
        $this->assertContains('votre code postal (zone de mobilité)', $user->getMissingProfileElements());

        $this->assertContains('un métier préféré ou une famille ROME', $user->getMissingProfileElements());
        $this->assertContains('5 compétence(s) technique(s) supplémentaire(s)', $user->getMissingProfileElements());
    }

    public function test_is_profile_mature_returns_true_when_all_elements_present(): void
    {
        $user = User::factory()->create(['zip_code' => '1000']);

        $metier = Metier::create(['label' => 'Developper', 'code' => 'M1805']);
        $user->preferredMetiers()->attach($metier->id, ['status' => 'favorite']);

        for ($i = 1; $i <= 5; $i++) {
            $skill = Skill::create(['label' => "Skill $i", 'code' => "S$i", 'type' => 'hard']);
            $user->skills()->attach($skill->id, ['status' => 'active', 'level' => 'expert']);
        }

        $this->assertTrue($user->isProfileMature());
        $this->assertEmpty($user->getMissingProfileElements());
    }

    public function test_is_online_returns_true_when_recently_seen(): void
    {
        $user = User::factory()->create(['last_seen_at' => Carbon::now()->subMinutes(10)]);
        $this->assertTrue($user->isOnline());
    }

    public function test_is_online_returns_false_when_not_recently_seen(): void
    {
        $user = User::factory()->create(['last_seen_at' => Carbon::now()->subMinutes(20)]);
        $this->assertFalse($user->isOnline());
    }

    public function test_is_online_returns_false_when_never_seen(): void
    {
        $user = User::factory()->create(['last_seen_at' => null]);
        $this->assertFalse($user->isOnline());
    }

    public function test_use_ai_point_increments_usage(): void
    {
        $user = User::factory()->create([
            'daily_ai_limit' => 5,
            'daily_ai_usage' => 0,
            'last_ai_usage_at' => null,
        ]);

        $this->assertTrue($user->useAiPoint());

        $user->refresh();
        $this->assertEquals(1, $user->daily_ai_usage);
        $this->assertNotNull($user->last_ai_usage_at);
    }

    public function test_use_ai_point_fails_when_limit_reached(): void
    {
        $user = User::factory()->create([
            'daily_ai_limit' => 5,
            'daily_ai_usage' => 5,
            'last_ai_usage_at' => Carbon::now(),
        ]);

        $this->assertFalse($user->useAiPoint());

        $user->refresh();
        $this->assertEquals(5, $user->daily_ai_usage);
    }

    public function test_use_ai_point_resets_daily_usage_on_new_day(): void
    {
        $user = User::factory()->create([
            'daily_ai_limit' => 5,
            'daily_ai_usage' => 5,
            'last_ai_usage_at' => Carbon::now()->subDays(2),
        ]);

        $this->assertTrue($user->useAiPoint());

        $user->refresh();
        $this->assertEquals(1, $user->daily_ai_usage);
    }
}
