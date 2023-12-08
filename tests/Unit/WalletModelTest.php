<?php

namespace Tests\Unit;

use App\Enums\DepositStatus;
use App\Enums\WithdrawStatus;
use App\Models\Deposit;
use App\Models\Wallet;
use App\Models\Withdraw;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use App\Services\Tron;
use App\Models\Agent;
use App\Models\User;
use Str;
use Tests\TestCase;

class WalletModelTest extends TestCase
{
    use RefreshDatabase;
    public function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }


    public function test_find_available_return_wallet_if_amount_is_different(): void
    {
        $wallet = Wallet::factory()->for(Agent::factory())->state(['activated_at' => now()])->create();
        $amount = 1;
        Deposit::factory()->for(
            User::factory()->for(Agent::factory())
        )
            ->create([
                'wallet_id' => $wallet->id,
                'amount' => $amount,
            ]);

        $this->assertNull(Wallet::findAvailable($wallet->agent_id, $amount));
        $amount++;
        $this->assertNotNull(Wallet::findAvailable($wallet->agent_id, $amount));

        Deposit::factory()->for(
            User::factory()->for(Agent::factory())
        )
            ->create([
                'wallet_id' => $wallet->id,
                'amount' => $amount,
            ]);

        $this->assertNull(Wallet::findAvailable($wallet->agent_id, $amount));
        $amount++;
        $this->assertNotNull(Wallet::findAvailable($wallet->agent_id, $amount));
    }

    public function test_find_available_return_wallet_that_is_activated(): void
    {
        $noActiveWallet = Wallet::factory()->for(Agent::factory())->state(['activated_at' => null])->create();
        $this->assertDatabaseCount('wallets', 1);
        $this->assertNull(Wallet::findAvailable($noActiveWallet->agent_id, rand(1, 5)));

        $activeWallet = Wallet::factory()->for(Agent::factory())->state(['activated_at' => now()])->create();

        $this->assertDatabaseCount('wallets', 2);
        $this->assertNotNull(Wallet::findAvailable($activeWallet->agent_id, rand(1, 5)));
    }

    public function test_find_available_return_wallet_that_is_owned_by_the_agent(): void
    {
        $wallet = Wallet::factory()->for(Agent::factory())->state(['activated_at' => now()])->create();
        $this->assertDatabaseCount('wallets', 1);
        $this->assertNotNull(Wallet::findAvailable($wallet->agent_id, rand(1, 5)));

        $this->assertNull(Wallet::findAvailable(rand($wallet->agent_id+1, $wallet->agent_id+10), rand(1, 5)));
    }

    public function test_find_available_return_wallet_that_does_not_have_pending_or_confirmed_deposit_associated(): void
    {
        $agent = Agent::factory()->create();
        $wallet = Wallet::factory()->for($agent)->state(['activated_at' => now()])->create();
        $this->assertDatabaseCount('wallets', 1);

        $pendingDeposit = Deposit::factory()->for(User::factory()->for($agent)->create())->for(Wallet::factory()->for($agent)->create())->state(['status' => DepositStatus::PENDING, 'wallet_id' => $wallet->id])->create();
        $this->assertDatabaseCount('deposits', 1);

        $this->assertNull(Wallet::findAvailable($wallet->agent_id, $pendingDeposit->amount));

        $confirmDeposit = Deposit::where('id', $pendingDeposit->id)->update(['status' => DepositStatus::CONFIRMED]);
        $this->assertNull(Wallet::findAvailable($wallet->agent_id, $pendingDeposit->amount));

        $otherStatusDeposit = Deposit::where('id', $pendingDeposit->id)->update(['status' => rand(3, 5)]);
        $this->assertNotNull(Wallet::findAvailable($agent->id, $pendingDeposit->amount));
    }

    public function test_witdrawable_return_wallet_that_is_activated(): void
    {

        Http::fake([
            config('app')['tron_api_url'] . '/v1/accounts/*' => Http::response(['data' => [
                [
                    "balance" => rand(5, 10) * Tron::DIGITS,
                    "address" => Str::random(42),
                    "create_time" => now(),
                    "trc20" => [[config('app')['trc20_address'] => rand(5, 10) * Tron::DIGITS]],
                    "frozenV2" => [["type" => "ENERGY"], ["type" => "UNKNOWN_ENUM_VALUE_ResourceCode_2"]],
                ]
            ]]),
        ]);
        Http::fake([
            config('app')['tron_api_url'] . '/wallet/getaccountresource' => Http::response([
                "freeNetUsed" => rand(500, 600),
                "freeNetLimit" => rand(600, 700),
            ])
        ]);

        $agent = Agent::factory()->create();
        $wallet = Wallet::create([
            'base58_check' => 'TDqVegmPEb3juuAV4vZYNS5AWUbvTUFH3y',
            'activated_at' => now(),
            'trx' => (config('app')['min_trx_for_transaction']) * Tron::DIGITS,
            'energy' => config('app')['min_energy_for_transaction'],
            'bandwidth' => 5000,
            'balance' => rand(1, 5) * Tron::DIGITS,
            'base64' => 'QSprErfAdul49mu5fe+Ut8qEoFQy',
            'private_key' => '40f1a63332a869f3c1ab4c07c1dba94d0fbc019dc88ef796bb1b147c0e15795e',
            'public_key' => '0404231eadf61b9251364d131a855b6bead6a4a3f2f58d3c4578c03b3a235db490746cf2e6bb33fd98dd2a30856d3296a04669d41696d93cb193ef731ee97a3e9a',
            'hex_address' => '412a6b12b7c076e978f66bb97def94b7ca84a05432',
            'agent_id'  => $agent->id
        ]);
        $withdrawAmount = $wallet->getRawOriginal('balance');
        $this->assertNotNull(Wallet::withdrawable($agent->id ,$withdrawAmount));

        $wallet->update(['activated_at' => null]);

        $this->assertNull(Wallet::withdrawable($agent->id ,$withdrawAmount));
    }

    public function test_withdrawable_return_wallet_that_have_enough_balance(): void
    {
        Http::fake([
            config('app')['tron_api_url'] . '/v1/accounts/*' => Http::response(['data' => [
                [
                    "balance" => rand(5, 10) * Tron::DIGITS,
                    "address" => Str::random(42),
                    "create_time" => now(),
                    "trc20" => [[config('app')['trc20_address'] => rand(5, 10) * Tron::DIGITS]],
                    "frozenV2" => [["type" => "ENERGY"], ["type" => "UNKNOWN_ENUM_VALUE_ResourceCode_2"]],
                ]
            ]]),
        ]);
        Http::fake([
            config('app')['tron_api_url'] . '/wallet/getaccountresource' => Http::response([
                "freeNetUsed" => rand(500, 600),
                "freeNetLimit" => rand(600, 700),
            ])
        ]);
        $agent = Agent::factory()->create();
        $wallet = Wallet::factory()->create([
            'base58_check' => 'TDqVegmPEb3juuAV4vZYNS5AWUbvTUFH3y',
            'balance' =>  rand(1, 5) * Tron::DIGITS,
            'activated_at' => now(),
            'trx' => (config('app')['min_trx_for_transaction']) * Tron::DIGITS,
            'energy' => config('app')['min_energy_for_transaction'],
            'bandwidth' => 5000,
            'agent_id'  => $agent->id
        ]);

        $withdrawAmount = $wallet->getRawOriginal('balance');

        $this->assertNotNull(Wallet::withdrawable($agent->id, $withdrawAmount));

        Withdraw::factory()->for(User::factory()->for($agent)->create())->create(['amount' => $withdrawAmount, 'status' => WithdrawStatus::PENDING->value, 'wallet_id' => $wallet->id]);
        $this->assertDatabaseCount('withdraws', 1);

        $this->assertNull(Wallet::withdrawable($agent->id, rand(1, 5) * Tron::DIGITS));
    }
}
