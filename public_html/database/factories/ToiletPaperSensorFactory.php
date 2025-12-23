<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ToiletPaperSensorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $max      = $this->faker->randomFloat(2, 25.0, 30.0);
        $critical = bcmul(0.9, $max);

        return [
            'name'           => $this->faker->colorName(),
            'min_value'      => $this->faker->randomFloat(2, 0.0, 5.0),
            'max_value'      => $max,
            'critical_value' => $critical,
        ];
    }
}
