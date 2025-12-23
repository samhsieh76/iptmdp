<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class HumanTrafficSensorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'           => $this->faker->firstNameMale(),
            'critical_value' => $this->faker->randomElement([200, 400, 600, 800]),
        ];
    }
}
