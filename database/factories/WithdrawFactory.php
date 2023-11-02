<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WithdrawFactory extends Factory
{

    public function definition(): array
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        return [
            'user_id' => 1 ,
            'wallet_id' => 1,
            'to' => 'TDqVegmPEb3juuAV4vZYNS5AWUbvTUFH3y',
            'amount' => rand(1, 5),
            'fee' => 1,
            'status' => 1,
            'txid' => Str::random(16),
            'transaction_id'    => rand(1, 5),
            'attempts'    => 0,
        ];
    }
}
