<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'      => $this->faker->safeColorName(),
            'auth_code' => $this->faker->regexify('[A-Za-z0-9]{10}'),
        ];
    }
}
