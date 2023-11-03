<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TransferFactory extends Factory
{

    public function definition(): array
    {
        return [
            'user_id' => rand(1, 5),
            'recipient_id' => rand(6, 10),
            'amount' => rand(2, 5),
            'fee' => 1,
        ];
    }
}
