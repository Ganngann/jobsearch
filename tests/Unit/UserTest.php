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

    public function test_progress_calculations(): void
    {
        $user = User::factory()->create(['zip_code' => null]);

        $this->assertEquals(0, $user->getNarrativeProgress());
        $this->assertEquals(0, $user->getSkillsProgress());
        $this->assertEquals(0, $user->getRomeProgress());
        $this->assertEquals(0, $user->getMobilityProgress());
        $this->assertEquals(0, $user->getProfileCompletionAttribute());

        // Mobility
        $user->zip_code = '1000';
        $user->save();
        $this->assertEquals(100, $user->getMobilityProgress());

        // Rome
        $metier = Metier::create(['label' => "Metier 1", 'code' => "M1"]);
        $user->preferredMetiers()->attach($metier->id, ['status' => 'favorite']);
        $this->assertEquals(33, $user->getRomeProgress());

        $metier2 = Metier::create(['label' => "Metier 2", 'code' => "M2"]);
        $user->preferredMetiers()->attach($metier2->id, ['status' => 'favorite']);
        $metier3 = Metier::create(['label' => "Metier 3", 'code' => "M3"]);
        $user->preferredMetiers()->attach($metier3->id, ['status' => 'favorite']);
        $this->assertEquals(100, $user->getRomeProgress());

        // Skills
        for ($i = 1; $i <= 25; $i++) {
            $skill = Skill::create(['label' => "Skill $i", 'code' => "S$i", 'type' => 'hard']);
            $user->skills()->attach($skill->id, ['status' => 'active', 'level' => 'expert']);
        }
        $this->assertEquals(50, $user->getSkillsProgress());

        for ($i = 26; $i <= 50; $i++) {
            $skill = Skill::create(['label' => "Skill $i", 'code' => "S$i", 'type' => 'hard']);
            $user->skills()->attach($skill->id, ['status' => 'active', 'level' => 'expert']);
        }
        $this->assertEquals(100, $user->getSkillsProgress());

        // Narrative
        for ($i = 1; $i <= 10; $i++) {
            $user->facts()->create(['content' => "Fact $i"]);
        }
        $this->assertEquals(35, $user->getNarrativeProgress());

        for ($i = 11; $i <= 20; $i++) {
            $user->facts()->create(['content' => "Fact $i"]);
        }
        $this->assertEquals(70, $user->getNarrativeProgress());

        $user->experiences()->create([
            'title' => "Exp 1",
            'company' => "Comp 1",
            'start_date' => now()->subYear(),
            'description' => "Desc 1"
        ]);
        $this->assertEquals(80, $user->getNarrativeProgress());

        $user->experiences()->create([
            'title' => "Exp 2",
            'company' => "Comp 2",
            'start_date' => now()->subYear(),
            'description' => "Desc 2"
        ]);
        $user->educations()->create([
            'degree' => "Edu 1",
            'institution' => "Inst 1",
            'graduation_year' => 2020
        ]);
        $this->assertEquals(100, $user->getNarrativeProgress());

        // Overall completion
        $this->assertEquals(100, $user->getProfileCompletionAttribute());
    }

    public function test_relationships(): void
    {
        $user = new User();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $user->skills());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $user->validatedSkills());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $user->languages());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $user->permits());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->matches());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->facts());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->profileMessages());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->profileSessions());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $user->preferredMetiers());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $user->preferredReferentielMetiers());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->discoverySuggestions());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->experiences());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->educations());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->projects());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->certifications());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->interests());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->volunteerExperiences());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->aiLogs());
    }

    public function test_is_profile_mature_returns_false_when_elements_missing(): void
    {
        $user = User::factory()->create(['zip_code' => null]);

        $this->assertFalse($user->isProfileMature());
        $this->assertContains('votre zone de mobilité (code postal)', $user->getMissingProfileElements());

        $this->assertContains('au moins 3 métiers favoris', $user->getMissingProfileElements());
        $this->assertContains('au moins 50 compétences validées', $user->getMissingProfileElements());
        $this->assertContains('un récit complet (faits et expériences)', $user->getMissingProfileElements());
    }

    public function test_is_profile_mature_returns_true_when_all_elements_present(): void
    {
        $user = User::factory()->create(['zip_code' => '1000']);

        // 3 métiers favoris
        for ($i = 1; $i <= 3; $i++) {
            $metier = Metier::create(['label' => "Metier $i", 'code' => "M$i"]);
            $user->preferredMetiers()->attach($metier->id, ['status' => 'favorite']);
        }

        // 50 skills
        for ($i = 1; $i <= 50; $i++) {
            $skill = Skill::create(['label' => "Skill $i", 'code' => "S$i", 'type' => 'hard']);
            $user->skills()->attach($skill->id, ['status' => 'active', 'level' => 'expert']);
        }

        // Narrative: 20 facts and 3 experiences
        for ($i = 1; $i <= 20; $i++) {
            $user->facts()->create(['content' => "Fact $i"]);
        }
        for ($i = 1; $i <= 3; $i++) {
            $user->experiences()->create([
                'title' => "Exp $i",
                'company' => "Comp $i",
                'start_date' => now()->subYear(),
                'description' => "Desc $i"
            ]);
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

    public function test_use_ai_point_with_model(): void
    {
        $user = User::factory()->create([
            'daily_ai_limit' => 10,
            'daily_ai_limits' => ['model-a' => 2, 'model-b' => 5],
            'daily_ai_usage' => 0,
            'daily_ai_usage_breakdown' => [],
            'last_ai_usage_at' => null,
        ]);

        $this->assertTrue($user->useAiPoint('model-a'));
        $this->assertTrue($user->useAiPoint('model-a'));
        $this->assertFalse($user->useAiPoint('model-a')); // limit 2

        $this->assertTrue($user->useAiPoint('model-b'));
        $this->assertTrue($user->useAiPoint('model-c')); // falls back to setting/global 10

        $user->refresh();
        $this->assertEquals(4, $user->daily_ai_usage);
        $this->assertEquals(2, $user->daily_ai_usage_breakdown['model-a']);
        $this->assertEquals(1, $user->daily_ai_usage_breakdown['model-b']);
        $this->assertEquals(1, $user->daily_ai_usage_breakdown['model-c']);
    }

    public function test_get_ai_remaining_points(): void
    {
        $user = User::factory()->create([
            'daily_ai_limit' => 10,
            'daily_ai_limits' => ['model-a' => 3],
            'daily_ai_usage' => 5,
            'daily_ai_usage_breakdown' => ['model-a' => 1],
            'last_ai_usage_at' => now(),
        ]);

        $this->assertEquals(5, $user->getAiRemainingPoints());
        $this->assertEquals(2, $user->getAiRemainingPoints('model-a'));
        $this->assertEquals(10, $user->getAiRemainingPoints('model-b')); // limit fallback - 0 usage
    }

    public function test_get_ai_remaining_points_resets_on_new_day(): void
    {
        $user = User::factory()->create([
            'daily_ai_limit' => 10,
            'daily_ai_limits' => ['model-a' => 3],
            'daily_ai_usage' => 10,
            'daily_ai_usage_breakdown' => ['model-a' => 3],
            'last_ai_usage_at' => now()->subDay(),
        ]);

        $this->assertEquals(10, $user->getAiRemainingPoints());
        $this->assertEquals(3, $user->getAiRemainingPoints('model-a'));
        $this->assertEquals(10, $user->getAiRemainingPoints('model-b'));
    }

    public function test_profile_updated_at(): void
    {
        $user = User::factory()->create();
        $baseDate = $user->updated_at;

        $this->assertEquals($baseDate->timestamp, $user->profileUpdatedAt()->timestamp);

        // Update a relationship
        $skill = Skill::create(['label' => "Skill", 'code' => "S", 'type' => 'hard']);
        $user->skills()->attach($skill->id, ['status' => 'active', 'level' => 'expert', 'updated_at' => now()->addDay()]);

        $this->assertTrue($user->profileUpdatedAt()->gt($baseDate));
    }

    public function test_is_profile_dirty(): void
    {
        $user = User::factory()->create(['profile_published_at' => null]);
        $this->assertTrue($user->isProfileDirty()); // Never published

        $user->profile_published_at = now();
        $user->save();

        $this->assertFalse($user->isProfileDirty()); // Just published

        $skill = Skill::create(['label' => "Skill", 'code' => "S", 'type' => 'hard']);
        $user->skills()->attach($skill->id, ['status' => 'active', 'level' => 'expert', 'updated_at' => now()->addDay()]);

        $this->assertTrue($user->isProfileDirty()); // Updated since publish
    }
}
