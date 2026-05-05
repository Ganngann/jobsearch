<?php

namespace Database\Factories;

use App\Models\Experience;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExperienceFactory extends Factory
{
    protected $model = Experience::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company' => $this->faker->company(),
            'title' => $this->faker->jobTitle(),
            'start_date' => $this->faker->date(),
            'status' => 'validated',
        ];
    }
}
