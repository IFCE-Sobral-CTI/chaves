<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Rule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rule>
 */
class RuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'description' => fake()->word,
            'control' => fake()->word,
            'group_id' => Group::all()->random()->id,
        ];
    }
}
