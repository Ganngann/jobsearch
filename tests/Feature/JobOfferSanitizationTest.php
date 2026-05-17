<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\JobOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobOfferSanitizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_offer_outputs_are_sanitized_against_xss_with_description_job()
    {
        $user = User::factory()->create();
        $employer = Employer::factory()->create();

        $jobOffer = JobOffer::factory()->create([
            'employer_id' => $employer->id,
            'description' => '<p>Safe description</p><script>alert("xss description")</script>', // should not be shown when descriptionJob exists
            'raw_data' => [
                'descriptionJob' => '<b>Safe Job Desc</b><script>alert("xss job desc")</script>',
                'descriptionComment' => '<i>Safe Comment</i><img src=x onerror=alert("xss comment")>',
                'commentaireGeneral' => 'Safe General Comment<svg onload=alert("xss general")>',
                'benefitsComments' => 'Safe Benefits<iframe src="javascript:alert(\'xss benefits\')"></iframe>'
            ],
        ]);

        $response = $this->actingAs($user)->get(route('jobs.show', $jobOffer));

        $response->assertStatus(200);

        // Assert malicious scripts are removed
        $response->assertDontSee('<script>alert("xss description")</script>', false);
        $response->assertDontSee('<script>alert("xss job desc")</script>', false);
        $response->assertDontSee('onerror=alert("xss comment")', false);
        $response->assertDontSee('onload=alert("xss general")', false);
        $response->assertDontSee('iframe', false);

        // Assert safe HTML is preserved or partially formatted by purifier
        $response->assertSee('<b>Safe Job Desc</b>', false);
        $response->assertSee('Safe General Comment', false);
        $response->assertSee('Safe Benefits', false);
    }

    public function test_job_offer_outputs_are_sanitized_against_xss_with_only_description()
    {
        $user = User::factory()->create();
        $employer = Employer::factory()->create();

        $jobOffer = JobOffer::factory()->create([
            'employer_id' => $employer->id,
            'description' => '<p>Safe description fallback</p><script>alert("xss description")</script>',
            'raw_data' => [
                'descriptionComment' => '<i>Safe Comment</i><img src=x onerror=alert("xss comment")>',
                'commentaireGeneral' => 'Safe General Comment<svg onload=alert("xss general")>',
                'benefitsComments' => 'Safe Benefits<iframe src="javascript:alert(\'xss benefits\')"></iframe>'
            ],
        ]);

        $response = $this->actingAs($user)->get(route('jobs.show', $jobOffer));

        $response->assertStatus(200);

        // Assert malicious scripts are removed
        $response->assertDontSee('<script>alert("xss description")</script>', false);
        $response->assertDontSee('onerror=alert("xss comment")', false);
        $response->assertDontSee('onload=alert("xss general")', false);
        $response->assertDontSee('iframe', false);

        // Assert safe HTML is preserved or partially formatted by purifier
        $response->assertSee('Safe description fallback', false);
        $response->assertSee('Safe General Comment', false);
        $response->assertSee('Safe Benefits', false);
    }
}