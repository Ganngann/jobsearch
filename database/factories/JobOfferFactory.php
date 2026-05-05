<?php

namespace Database\Factories;

use App\Models\JobOffer;
use App\Models\Metier;
use App\Models\Employer;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobOfferFactory extends Factory
{
    protected $model = JobOffer::class;

    public function definition(): array
    {
        return [
            'forem_id' => $this->faker->unique()->randomNumber(9),
            'forem_ref' => $this->faker->unique()->word(),
            'title' => $this->faker->jobTitle(),
            'metier_id' => Metier::factory(),
            'employer_id' => Employer::factory(),
            'description' => $this->faker->paragraphs(3, true),
            'location' => $this->faker->city(),
            'contract_type' => 'CDI',
            'working_regime' => 'Temps plein',
            'published_at' => now(),
            'status' => 'active',
        ];
    }
}
