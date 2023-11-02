<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'from' => 'TWxQ5m1TMLumFH7bMws4Q1qoP1FeYfkhKc',
            'to' => 'TDqVegmPEb3juuAV4vZYNS5AWUbvTUFH3y',
            'transaction_id' => Str::random(64),
            'token_address' => 'TG3XXyExBkPp9nzdajDZsozEu4BkaSJozs',
            'block_timestamp' => time(),
            'value' => '5000000',
            'type' => 'Transfer',
            'fee' => '5478900',
            'receipt' => '{"energy_fee":5478900,"energy_usage_total":13045,"net_usage":346,"result":"SUCCESS"}',
        ];
    }
}
