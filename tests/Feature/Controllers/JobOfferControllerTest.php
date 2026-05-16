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
}
