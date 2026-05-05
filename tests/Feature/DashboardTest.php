<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\JobOffer;
use App\Models\Metier;
use App\Models\Employer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup base data for dashboard
        $this->user = User::factory()->create();
        
        // Create some métiers and employers
        Metier::factory()->count(5)->create();
        Employer::factory()->count(5)->create();
        
        // Create job offers
        JobOffer::factory()->count(10)->create();

        // Make user profile mature to avoid redirections
        $this->user->update(['zip_code' => '1000']);
        // We'll use a partial mock or just seed what's needed.
        // For simplicity in this test, let's just bypass the mature check if we can,
        // or just seed 50 skills and 3 metiers.
        // Actually, seeding is more robust.
        \App\Models\Skill::factory()->count(50)->create();
        $this->user->skills()->attach(\App\Models\Skill::all()->pluck('id'), ['status' => 'active']);
        $this->user->preferredMetiers()->attach(\App\Models\Metier::limit(3)->pluck('id'), ['status' => 'favorite']);
        // Facts for narrative progress
        \App\Models\UserFact::factory()->count(30)->create(['user_id' => $this->user->id]);
        \App\Models\Experience::factory()->count(3)->create(['user_id' => $this->user->id]);
    }

    #[Test]
    public function dashboard_page_is_accessible()
    {
        $response = $this->actingAs($this->user)
                         ->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertSee('Offres Emploi');
        $response->assertSee('dashboardApp'); // Ensure the Alpine component is referenced
    }

    #[Test]
    public function dashboard_can_be_filtered_by_metier()
    {
        $metier = Metier::first();
        
        $response = $this->actingAs($this->user)
                         ->get("/dashboard?metier_id={$metier->id}&partial=1");

        $response->assertStatus(200);
        // Partial should return only the items
        $response->assertViewIs('job-offers.partials.list-items');
    }

    #[Test]
    public function dashboard_can_search_offers()
    {
        $offer = JobOffer::first();
        $query = substr($offer->title, 0, 5);
        
        $response = $this->actingAs($this->user)
                         ->get("/dashboard?q={$query}&partial=1");

        $response->assertStatus(200);
    }

    #[Test]
    public function dashboard_job_preview_is_accessible()
    {
        $offer = JobOffer::first();
        
        $response = $this->actingAs($this->user)
                         ->get("/jobs/{$offer->forem_id}/preview");

        $response->assertStatus(200);
    }
}
