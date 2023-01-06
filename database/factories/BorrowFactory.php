<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Borrow>
 */
class BorrowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $rand = rand(0, 1);
        return [
            'devolution' => $rand? now(): null,
            'observation' => fake()->sentence(),
            'employee_id' => Employee::all()->random()->id,
            'user_id' => 1,
        ];
    }
}
