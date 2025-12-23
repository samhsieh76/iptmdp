<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ToiletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'type'        => $this->faker->randomElement([1, 2, 3]),
            'code'        => strtoupper($this->faker->randomLetter()) . $this->faker->randomNumber(9),
            'name'        => $this->faker->name(),
            'image'       => $this->faker->imageUrl(),
            'device_key'  => $this->faker->text(50),
            'alert_token' => $this->faker->text(50),
        ];
    }
}
