<?php

namespace Tests\Unit;

use App\Enums\DepositStatus;
use App\Models\Agent;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Tron;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Http;
use Str;

class UserModelTest extends TestCase
{
    use RefreshDatabase;
    public function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Http::preventStrayRequests();

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
    }

    public function test_find_active_user_deposit_that_user_has_pending_or_confirmed_deposit(): void
    {
        $amount = 1;
        $agent = Agent::factory()->create();
        $deposit = Deposit::factory()->for(
            User::factory()->for($agent)
        )->for(Wallet::factory()->for($agent))
            ->create([
                'amount' => $amount
            ]);

        $user = $deposit->user;
        $this->assertNotNull($user->getActiveDeposit($amount));

        $confirmedDeposit = $deposit->update(['status' => DepositStatus::CONFIRMED->value]);
        $this->assertNotNull($user->getActiveDeposit($amount));

        $diff_amount = 2;
        $this->assertNull($user->getActiveDeposit($diff_amount));

        $completedDeposit = $deposit->update(['status' => DepositStatus::COMPLETED->value]);
        $this->assertNull($user->getActiveDeposit($amount));
    }
}
