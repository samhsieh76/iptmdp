<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class HumanTrafficLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'raw_data' => $this->faker->randomNumber(2),
        ];
    }
}
