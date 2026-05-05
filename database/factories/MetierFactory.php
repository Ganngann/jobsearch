<?php

namespace Database\Factories;

use App\Models\Metier;
use Illuminate\Database\Eloquent\Factories\Factory;

class MetierFactory extends Factory
{
    protected $model = Metier::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->bothify('??###'),
            'guid' => $this->faker->uuid(),
            'label' => $this->faker->jobTitle(),
        ];
    }
}
