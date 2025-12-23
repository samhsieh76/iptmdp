<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->name();
        $username = explode(" ", $name);
        return [
            'role_id'        => $this->faker->randomElement([2, 3, 4, 5]),
            'username'        => strtolower($username[1]),
            'email'        => $this->faker->unique()->safeEmail(),
            'name'        => $name,
            'password'       => 'password',
        ];
    }
}
