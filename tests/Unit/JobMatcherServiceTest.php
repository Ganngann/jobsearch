<?php

namespace Tests\Unit;

use App\Models\JobOffer;
use App\Models\User;
use App\Models\Employer;
use App\Models\Skill;
use App\Models\Language;
use App\Models\Permit;
use App\Services\JobMatcherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobMatcherServiceTest extends TestCase
{
    use RefreshDatabase;

    protected JobMatcherService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new JobMatcherService();
    }

    public function test_calculate_hard_score_returns_correct_structure(): void
    {
        $user = User::factory()->create(['zip_code' => '1000']);
        $employer = Employer::create(['label' => 'Test Employer']);

        $jobOffer = JobOffer::create([
            'forem_id' => '123456',
            'forem_ref' => 'REF123',
            'employer_id' => $employer->id,
            'contract_type' => 'CDI',
            'working_regime' => 'Temps plein',
            'title' => 'Test Job',
            'location' => '1000 Bruxelles',
            'is_detailed' => true,
        ]);

        $result = $this->service->calculateHardScore($user, $jobOffer);

        $this->assertArrayHasKey('total_score', $result);
        $this->assertArrayHasKey('details', $result);
        $this->assertArrayHasKey('skills', $result['details']);
        $this->assertArrayHasKey('languages', $result['details']);
        $this->assertArrayHasKey('permits', $result['details']);
        $this->assertArrayHasKey('location', $result['details']);
    }

    public function test_calculate_hard_score_calculates_correctly_with_exact_match(): void
    {
        $user = User::factory()->create(['zip_code' => '1000']);

        $skill = Skill::create(['label' => 'PHP', 'code' => 'S123', 'type' => 'hard']);
        $user->skills()->attach($skill->id, ['status' => 'active', 'level' => 'expert']);

        $lang = Language::create(['label' => 'French', 'code' => 'FR']);
        $user->languages()->attach($lang->id, ['level' => 'fluent']);

        $permit = Permit::create(['label' => 'B', 'code' => 'B', 'value' => 'B']);
        $user->permits()->attach($permit->id);

        $employer = Employer::create(['label' => 'Test Employer']);

        $jobOffer = JobOffer::create([
            'forem_id' => '1234567',
            'forem_ref' => 'REF1234',
            'employer_id' => $employer->id,
            'contract_type' => 'CDI',
            'working_regime' => 'Temps plein',
            'title' => 'Test Job',
            'location' => '1000 Bruxelles',
            'is_detailed' => true,
        ]);

        $jobOffer->skills()->attach($skill->id, ['is_required' => true]);
        $jobOffer->languages()->attach($lang->id, ['is_required' => true, 'level' => 'fluent']);
        $jobOffer->permits()->attach($permit->id, ['is_required' => true]);

        $result = $this->service->calculateHardScore($user, $jobOffer);

        $this->assertEquals(100, $result['total_score']);
        $this->assertEquals(100, $result['details']['skills']['score']);
        $this->assertEquals(100, $result['details']['languages']['score']);
        $this->assertEquals(100, $result['details']['permits']['score']);
        $this->assertEquals(100, $result['details']['location']['score']);
    }

    public function test_calculate_hard_score_calculates_correctly_with_missing_skills(): void
    {
        $user = User::factory()->create(['zip_code' => '1000']);

        $skillMissing = Skill::create(['label' => 'Java', 'code' => 'S456', 'type' => 'hard']);

        $employer = Employer::create(['label' => 'Test Employer']);

        $jobOffer = JobOffer::create([
            'forem_id' => '12345678',
            'forem_ref' => 'REF12345',
            'employer_id' => $employer->id,
            'contract_type' => 'CDI',
            'working_regime' => 'Temps plein',
            'title' => 'Test Job',
            'location' => '1000 Bruxelles',
            'is_detailed' => true,
        ]);

        $jobOffer->skills()->attach($skillMissing->id, ['is_required' => true]);

        $result = $this->service->calculateHardScore($user, $jobOffer);

        // Required skill missing: base = 0, penalty = 0 * 0.7 = 0
        $this->assertEquals(0, $result['details']['skills']['score']);

        // Total score = (0 * 0.5) + (100 * 0.2) + (100 * 0.1) + (100 * 0.2) = 0 + 20 + 10 + 20 = 50
        $this->assertEquals(50, $result['total_score']);
    }

    public function test_calculate_hard_score_location_mismatch(): void
    {
        $user = User::factory()->create(['zip_code' => '4000']); // Liege

        $employer = Employer::create(['label' => 'Test Employer']);

        $jobOffer = JobOffer::create([
            'forem_id' => '123456789',
            'forem_ref' => 'REF123456',
            'employer_id' => $employer->id,
            'contract_type' => 'CDI',
            'working_regime' => 'Temps plein',
            'title' => 'Test Job',
            'location' => '1000 Bruxelles', // Bxl
            'is_detailed' => true,
        ]);

        $result = $this->service->calculateHardScore($user, $jobOffer);

        $this->assertEquals(70, $result['details']['location']['score']);
        $this->assertEquals('À vérifier (distance)', $result['details']['location']['message']);
    }
    public function test_calculate_hard_score_ignores_non_active_skills(): void
    {
        $user = User::factory()->create(['zip_code' => '1000']);
        $employer = Employer::create(['label' => 'Test Employer']);

        $activeSkill = Skill::create(['label' => 'Active', 'code' => 'S1', 'type' => 'hard']);
        $draftSkill = Skill::create(['label' => 'Draft', 'code' => 'S2', 'type' => 'hard']);
        $refusedSkill = Skill::create(['label' => 'Refused', 'code' => 'S3', 'type' => 'hard']);

        $user->skills()->attach($activeSkill->id, ['status' => 'active', 'level' => 'expert']);
        $user->skills()->attach($draftSkill->id, ['status' => 'draft', 'level' => 'expert']);
        $user->skills()->attach($refusedSkill->id, ['status' => 'refused', 'level' => 'expert']);

        $jobOffer = JobOffer::create([
            'forem_id' => '111',
            'forem_ref' => 'REF111',
            'employer_id' => $employer->id,
            'contract_type' => 'CDI',
            'working_regime' => 'Temps plein',
            'title' => 'Test Job',
            'location' => '1000 Bruxelles',
            'is_detailed' => true,
        ]);

        // Job has all three skills
        $jobOffer->skills()->attach([$activeSkill->id, $draftSkill->id, $refusedSkill->id]);

        $result = $this->service->calculateHardScore($user, $jobOffer);

        // Only the active skill should be matched
        // Matched = 1, Total = 3 => Score = (1/3) * 100 = 33.33
        $this->assertEquals(round(33.3333333333), round($result['details']['skills']['score']));
        $this->assertCount(1, $result['details']['skills']['matched']);
        $this->assertEquals('Active', $result['details']['skills']['matched'][0]['label']);
    }


    public function test_calculate_hard_score_with_missing_languages(): void
    {
        $user = User::factory()->create(['zip_code' => '1000']);
        $employer = Employer::create(['label' => 'Test Employer']);

        $langMissing = Language::create(['label' => 'Dutch', 'code' => 'NL']);

        $jobOffer = JobOffer::create([
            'forem_id' => '1234567890',
            'forem_ref' => 'REF1234567',
            'employer_id' => $employer->id,
            'contract_type' => 'CDI',
            'working_regime' => 'Temps plein',
            'title' => 'Test Job',
            'location' => '1000 Bruxelles',
            'is_detailed' => true,
        ]);

        $jobOffer->languages()->attach($langMissing->id, ['is_required' => true, 'level' => 'fluent']);

        $result = $this->service->calculateHardScore($user, $jobOffer);

        $this->assertEquals(0, $result['details']['languages']['score']);
        $this->assertCount(1, $result['details']['languages']['missing']);
        $this->assertEquals('Dutch', $result['details']['languages']['missing'][0]['label']);
    }

    public function test_calculate_hard_score_with_missing_permits(): void
    {
        $user = User::factory()->create(['zip_code' => '1000']);
        $employer = Employer::create(['label' => 'Test Employer']);

        $permitMissing = Permit::create(['label' => 'C', 'code' => 'C', 'value' => 'C']);

        $jobOffer = JobOffer::create([
            'forem_id' => '12345678901',
            'forem_ref' => 'REF12345678',
            'employer_id' => $employer->id,
            'contract_type' => 'CDI',
            'working_regime' => 'Temps plein',
            'title' => 'Test Job',
            'location' => '1000 Bruxelles',
            'is_detailed' => true,
        ]);

        $jobOffer->permits()->attach($permitMissing->id, ['is_required' => true]);

        $result = $this->service->calculateHardScore($user, $jobOffer);

        $this->assertEquals(0, $result['details']['permits']['score']);
        $this->assertCount(1, $result['details']['permits']['missing']);
        $this->assertEquals('C', $result['details']['permits']['missing'][0]['label']);
    }

    public function test_calculate_hard_score_without_user_zip_code(): void
    {
        $user = User::factory()->create(['zip_code' => null]);
        $employer = Employer::create(['label' => 'Test Employer']);

        $jobOffer = JobOffer::create([
            'forem_id' => '123456789012',
            'forem_ref' => 'REF123456789',
            'employer_id' => $employer->id,
            'contract_type' => 'CDI',
            'working_regime' => 'Temps plein',
            'title' => 'Test Job',
            'location' => '1000 Bruxelles',
            'is_detailed' => true,
        ]);

        $result = $this->service->calculateHardScore($user, $jobOffer);

        $this->assertEquals(50, $result['details']['location']['score']);
        $this->assertEquals('Zone de mobilité non définie', $result['details']['location']['message']);
    }

}
