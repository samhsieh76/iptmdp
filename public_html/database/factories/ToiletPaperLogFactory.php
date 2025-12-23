<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ToiletPaperLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'raw_data' => $this->faker->randomFloat(2, 0.0, 30.0),
        ];
    }
}
