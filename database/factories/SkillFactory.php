<?php

namespace Database\Factories;

use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

class SkillFactory extends Factory
{
    protected $model = Skill::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->bothify('SK###'),
            'label' => $this->faker->word(),
            'type' => $this->faker->randomElement(['hard', 'soft']),
        ];
    }
}
