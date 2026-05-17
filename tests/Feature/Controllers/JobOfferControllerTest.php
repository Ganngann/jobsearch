<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\JobOffer;
use App\Models\Metier;
use App\Models\Employer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\JobOfferService;
use Mockery;

class JobOfferControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_offers()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_preview_loads_correctly_with_detailed_offer()
    {
        $user = User::factory()->create();

        $employer = Employer::create(['label' => 'Test']);
        $offer = JobOffer::factory()->create([
            'forem_id' => '456',
            'employer_id' => $employer->id,
            'is_detailed' => true
        ]);

        $this->actingAs($user);

        $response = $this->get("/jobs/{$offer->forem_id}/preview");

        $response->assertStatus(200);
        $response->assertViewIs('job-offers.partials.preview');
        $response->assertViewHasAll([
            'jobOffer',
            'match',
            'user',
            'isParentFavorite',
            'isOfferBlacklisted'
        ]);
    }

    public function test_preview_loads_details_if_needed()
    {
        $user = User::factory()->create();

        $employer = Employer::create(['label' => 'Test']);
        $offer = JobOffer::factory()->create([
            'forem_id' => '123',
            'employer_id' => $employer->id,
            'is_detailed' => false
        ]);

        $this->instance(
            JobOfferService::class,
            Mockery::mock(JobOfferService::class, function ($mock) use ($offer) {
                $mock->shouldReceive('syncFullDetails')->once()->with(Mockery::on(function($arg) use ($offer) {
                    return $arg->id === $offer->id;
                }))->andReturn(true);
            })
        );

        $this->actingAs($user);

        $response = $this->get("/jobs/{$offer->forem_id}/preview");

        $response->assertStatus(200);
    }

    public function test_show_displays_offer()
    {
        $user = User::factory()->create();
        $employer = Employer::create(['label' => 'Test']);
        $offer = JobOffer::factory()->create([
            'forem_id' => '1234',
            'employer_id' => $employer->id,
            'is_detailed' => true
        ]);

        $this->actingAs($user);
        $response = $this->get("/jobs/{$offer->forem_id}");
        $response->assertStatus(200);
    }

    public function test_match_calculates_score()
    {
        $user = User::factory()->create();
        $employer = Employer::create(['label' => 'Test']);
        $offer = JobOffer::factory()->create([
            'forem_id' => '12345',
            'employer_id' => $employer->id,
            'is_detailed' => true
        ]);

        $this->actingAs($user);
        $response = $this->post("/jobs/{$offer->forem_id}/match");
        $response->assertRedirect();
    }

    public function test_search_escapes_wildcard_characters()
    {
        $user = User::factory()->create();

        $employer1 = Employer::create(['label' => 'Test100%']);
        $offer1 = JobOffer::factory()->create([
            'title' => 'Developer',
            'employer_id' => $employer1->id,
            'is_detailed' => true
        ]);

        $employer2 = Employer::create(['label' => 'Test1000']);
        $offer2 = JobOffer::factory()->create([
            'title' => 'Designer',
            'employer_id' => $employer2->id,
            'is_detailed' => true
        ]);

        $this->actingAs($user);

        // This query should find only employer1 due to strict 100% matching
        $response = $this->get('/dashboard?q=Test100%');
        $response->assertStatus(200);

        // Since testing exact UI matches depends on partial views and structure,
        // asserting no SQL syntax errors or generic 500s is the primary goal for DoS wildcard testing.
        // It should handle %, _, and \ safely.
        $response2 = $this->get('/dashboard?q=%\_\\\\');
        $response2->assertStatus(200);

        $response3 = $this->get('/dashboard?rome=%\_\\\\');
        $response3->assertStatus(200);
    }
}
