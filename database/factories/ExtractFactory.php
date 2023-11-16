<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

class ExtractFactory extends Factory
{
    public function definition(): array
    {
        return [
            'to' => Str::random('32'),
            'amount' => fake()->numberBetween(1, 10),
            'type' => rand(1,2),
            'agent_extract_id' => fake()->unique()->numberBetween(1, 100)
        ];
    }
}
