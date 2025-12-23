<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RelativeHumidityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'raw_data' => $this->faker->randomFloat(2, 0.0, 100.0),
        ];
    }
}
