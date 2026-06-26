<?php

namespace Database\Factories;

use App\Models\Key;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Key>
 */
class KeyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'description' => ucfirst(fake()->word),
            'number' => fake()->unique()->numberBetween(1, 99999),
            'observation' => fake()->sentence(),
            'room_id' => Room::all()->random()->id,
        ];
    }
}
