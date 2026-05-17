<?php

namespace Database\Factories;

use App\Models\Permit;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermitFactory extends Factory
{
    protected $model = Permit::class;

    public function definition()
    {
        return [
            'code' => $this->faker->unique()->lexify('Permit-????'),
            'label' => $this->faker->word(),
            'value' => $this->faker->word(),
            'slug' => $this->faker->slug(),
        ];
    }
}
