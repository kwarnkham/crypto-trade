<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Agent;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use App\Services\Tron;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $agent = Agent::create([
            'name' => 'agent',
            'key' => 'aGqFAEV84uk1V4SvSP8IHcPxkOA5FMNv19XGl8cYztoG2I7ngGNErJhsaxNh74k9',
            'ip' => '*',
            'aes_key' => 'oPi4eLhhJ9lBV5oo'
        ]);

        if (!App::isProduction()) {
            Wallet::create([
                'private_key' => '40f1a63332a869f3c1ab4c07c1dba94d0fbc019dc88ef796bb1b147c0e15795e',
                'public_key' => '0404231eadf61b9251364d131a855b6bead6a4a3f2f58d3c4578c03b3a235db490746cf2e6bb33fd98dd2a30856d3296a04669d41696d93cb193ef731ee97a3e9a',
                'hex_address' => '412a6b12b7c076e978f66bb97def94b7ca84a05432',
                'base58_check' => 'TDqVegmPEb3juuAV4vZYNS5AWUbvTUFH3y',
                'base64' => 'QSprErfAdul49mu5fe+Ut8qEoFQy',
                'activated_at' => now(),
                'balance' => rand(5, 10) * Tron::DIGITS,
                'trx'   => config('app')['min_trx_for_transaction'],
                'energy'   => config('app')['min_energy_for_transaction'],
                'bandwidth'   => 500,
                'agent_id'  => $agent->id
            ]);

            Wallet::create([
                'private_key' => 'e992348282cbfc58410e143c53ba3b077f459a5920908d9b01e37d913803d17a',
                'public_key' => '045f6e7cfa48f7c6511a0594a1a86a51edb3be415978d9088646c8d35a5e6f57f0c1405cd03614b48eb7374e9840a75258168d5ea1b62697e0e436e2fe3b754ec9',
                'hex_address' => '41a37f366b83d13dc2cd8233485b31bd03526c0f71',
                'base58_check' => 'TQshYDGDZo67UhqyvvAEgXdAvYk9Lt62fJ',
                'base64' => 'QaN/NmuD0T3CzYIzSFsxvQNSbA9x',
                'activated_at' => now(),
                'agent_id'  => $agent->id
            ]);
        }

        DB::table('admins')->insert([
            'name' => 'admin',
            'password' => bcrypt('123123')
        ]);
    }
}
