<?php

namespace Database\Factories;

use App\Models\UserFact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactFactory extends Factory
{
    protected $model = UserFact::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'content' => $this->faker->sentence(),
            'category' => $this->faker->word(),
            'status' => 'validated',
        ];
    }
}
