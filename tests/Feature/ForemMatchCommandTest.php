<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\JobOffer;
use App\Services\MatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class ForemMatchCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_processes_all_users_and_job_offers()
    {
        $users = User::factory()->count(2)->create();
        $jobOffers = JobOffer::factory()->count(2)->create();

        $mock = Mockery::mock(MatchingService::class);
        $mock->shouldReceive('match')
             ->times(4); // 2 users * 2 jobs

        $this->instance(MatchingService::class, $mock);

        $this->artisan('forem:match')
             ->expectsOutput('Matching terminé !')
             ->assertExitCode(0);
    }
}
