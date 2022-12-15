<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'alternative_email' => fake()->unique()->safeEmail(),
            'tel' => fake()->phoneNumber(),
            'registry' => fake()->numberBetween(1000, 99999999999),
            'type' => fake()->numberBetween(1, 3),
        ];
    }
}
