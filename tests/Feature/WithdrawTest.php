<?php

namespace Tests\Feature;

use App\Enums\ResponseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessConfirmedWithdraw;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Enums\WithdrawStatus;
use App\Models\Agent;
use App\Models\Admin;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdraw;
use App\Services\Tron;
use Http;
use Tests\TestCase;

class WithdrawTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    private $wallet;
    private $to_wallet;
    private $agent;
    private $user;
    private $admin;

    public function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->seed();
        $this->actingAs(Admin::first());
        $this->agent = Agent::first();
        $this->wallet = Wallet::first();
        $this->to_wallet = 'TWxQ5m1TMLumFH7bMws4Q1qoP1FeYfkhKc';
        $this->user = User::create([
            'code' =>  Str::random('3'),
            'name' => $this->faker()->lastName(),
            'balance' => 5,
            'agent_id' =>   $this->agent->id,
        ]);
        $this->withHeaders([
            'x-agent'   => $this->agent->name,
            'x-api-key' => $this->agent->jwtKey(),
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json'
        ]);

        Http::preventStrayRequests();
        Http::fake([
            config('app')['tron_api_url'] . '/wallet/validateaddress' => Http::response([
                "result" => true,
                "message" => "Base58check format"
            ])
        ]);
    }

    public function test_agent_can_create_withdraw(): void
    {
        $response = $this->postJson('api/withdraws/agent', [
            'code' => $this->user->code,
            'to' => $this->to_wallet,
            'amount' => rand(2, 5),
            'agent_transaction_id' => Str::random(64),
        ]);

        $response->assertOk();
        $this->assertArrayHasKey('withdraw', $response->json());
        $this->assertDatabaseCount('withdraws', 1);
    }

    public function test_invalid_wallet_address_cannot_be_withdrawed(): void //................
    {
        $response = $this->postJson('api/withdraws/agent', [
            'code' => $this->user->code,
            'to' => Str::random(10),
            'amount' => rand(2, 5),
            'agent_transaction_id' => Str::random(64),
        ]);

        $response->assertStatus(ResponseStatus::UNPROCESSABLE_ENTITY->value);
    }

    public function test_agent_user_can_withdraw_to_wallet_address_only_that_does_not_exist_in_our_wallet_list(): void
    {
        $response = $this->postJson('api/withdraws/agent', [
            'code' => $this->user->code,
            'to' => $this->wallet->base58_check,
            'amount' => rand(2, 5),
            'agent_transaction_id' => Str::random(64),
        ]);

        $response->assertUnprocessable();

        $new_wallet = $this->to_wallet;
        $response = $this->postJson('api/withdraws/agent', [
            'code' => $this->user->code,
            'to' => $new_wallet,
            'amount' => rand(2, 5),
            'agent_transaction_id' => Str::random(64),
        ]);

        $response->assertOk();
    }

    public function test_agent_user_can_withdraw_only_if_balance_amount_is_greather_than_withdraw_amount(): void
    {
        $this->postJson('api/withdraws/agent', [
            'code' => $this->user->code,
            'to' => $this->to_wallet,
            'amount' => rand(6, 10),
            'agent_transaction_id' => Str::random(64),
        ])->assertUnprocessable();
        $this->assertDatabaseCount('withdraws', 0);


        $this->postJson('api/withdraws/agent', [
            'code' => $this->user->code,
            'to' => $this->to_wallet,
            'amount' => rand(2, 5),
            'agent_transaction_id' => Str::random(64),
        ])->assertOk();
        $this->assertDatabaseCount('withdraws', 1);
    }

    public function test_newly_created_withdraw_status_is_default_to_pending(): void
    {
        $response = $this->postJson('api/withdraws/agent', [
            'code' => $this->user->code,
            'to' => $this->to_wallet,
            'amount' => rand(2, 5),
            'agent_transaction_id' => Str::random(64),
        ]);

        $this->assertEquals(
            WithdrawStatus::PENDING->value,
            Withdraw::find($response->json()['withdraw']['id'])->status
        );
    }

    public function test_agent_user_can_confirm_withdraw(): void
    {
        Http::fake([
            config('app')['tron_api_url'] . '/v1/accounts/*' => Http::response(['data' => [
                [
                    "balance" => rand(10, 20) * Tron::DIGITS,
                    "address" => Str::random(42),
                    "create_time" => now(),
                    "trc20" => [[config('app')['trc20_address'] => rand(10, 20) * Tron::DIGITS]],
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

        Http::fake([
            config('app')['tron_api_url'] . '/wallet/triggersmartcontract' => Http::response([
                "transaction" => [
                    "visible" => true,
                    "txID" => bin2hex(random_bytes(32))
                ]
            ])
        ]);

        Http::fake([
            config('app')['tron_api_url'] . '/wallet/broadcasttransaction' => Http::response([
                "result" => true,
                "txid" => Str::random(64)
            ])
        ]);

        $response = $this->postJson('api/withdraws/agent', [
            'code' => $this->user->code,
            'to' => $this->to_wallet,
            'amount' => rand(2, 5),
            'agent_transaction_id' => Str::random(64),
        ]);

        $withdrawId = $response->json()['withdraw']['id'];

        $this->postJson('api/withdraws/agent/' . $withdrawId . '/confirm')->assertOk();
        Queue::assertPushed(function (ProcessConfirmedWithdraw $job) use ($withdrawId) {
            return $job->withdrawId === $withdrawId;
        });
    }

    public function test_withdraw_can_be_confirmed_only_if_withdraw_status_is_pending(): void
    {
        Http::fake([
            config('app')['tron_api_url'] . '/v1/accounts/*' => Http::response(['data' => [
                [
                    "balance" => rand(10, 20) * Tron::DIGITS,
                    "address" => Str::random(42),
                    "create_time" => now(),
                    "trc20" => [[config('app')['trc20_address'] => rand(10, 20) * Tron::DIGITS]],
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

        Http::fake([
            config('app')['tron_api_url'] . '/wallet/triggersmartcontract' => Http::response([
                "transaction" => [
                    "visible" => true,
                    "txID" => bin2hex(random_bytes(32))
                ]
            ])
        ]);

        Http::fake([
            config('app')['tron_api_url'] . '/wallet/broadcasttransaction' => Http::response([
                "result" => true,
                "txid" => Str::random(64)
            ])
        ]);

        $response = $this->postJson('api/withdraws/agent', [
            'code' => $this->user->code,
            'to' => $this->to_wallet,
            'amount' => rand(2, 5),
            'agent_transaction_id' => Str::random(64),
        ]);

        $withdrawId = $response->json()['withdraw']['id'];

        $confirmWithdraw = $this->postJson('api/withdraws/agent/' . $withdrawId . '/confirm');
        $confirmWithdraw->assertOk();
        Queue::assertPushed(function (ProcessConfirmedWithdraw $job) use ($withdrawId) {
            return $job->withdrawId === $withdrawId;
        });

        $this->assertNotEquals(
            WithdrawStatus::PENDING->value,
            $confirmWithdraw->json()['withdraw']['status']
        );

        $response = $this->postJson('api/withdraws/agent/' . $withdrawId . '/confirm');
        $response->assertBadRequest();
    }

    public function test_agent_user_can_list_withdraws(): void
    {
        Withdraw::factory()->count(5)->for(User::factory()->for(Agent::factory()->create())->create())->for(Wallet::factory()->create())->create();
        $response = $this->getJson('api/withdraws/agent');
        $response->assertOk();
        $this->assertArrayHasKey('data', $response->json());
        $this->assertNotEmpty($response->json()['data']);
    }

    public function test_admin_can_list_withdraws(): void
    {
        Withdraw::factory()->count(5)->for(User::factory()->for(Agent::factory()->create())->create())->for(Wallet::factory()->create())->create();
        $response = $this->getJson('api/withdraws');
        $response->assertOk();
        $this->assertArrayHasKey('data', $response->json());
        $this->assertNotEmpty($response->json()['data']);
    }

    public function test_agent_user_can_cancel_withdraw(): void
    {
        $withdrawCreate = Withdraw::factory()->for(User::factory()->for(Agent::factory()->create())->create())->for(Wallet::factory()->create())->create(['status' => WithdrawStatus::PENDING->value]);

        $this->assertEquals(
            WithdrawStatus::PENDING->value,
            $withdrawCreate->status
        );

        $this->postJson('api/withdraws/agent/' . $withdrawCreate->id . '/cancel')->assertOk();
        $this->assertEquals(
            WithdrawStatus::CANCELED->value,
            $withdrawCreate->refresh()->status
        );
    }

    public function test_only_pending_withdraw_can_be_cancelled(): void
    {
        $withdrawCreate = Withdraw::factory()->for(User::factory()->for(Agent::factory()->create())->create())->for(Wallet::factory()->create())->create(['status' => WithdrawStatus::PENDING->value]);
        $cancelResponse = $this->postJson('api/withdraws/agent/' .  $withdrawCreate->id . '/cancel');
        $cancelResponse->assertOk();

        $this->assertNotEquals(
            withdrawstatus::PENDING->value,
            $withdrawCreate->refresh()->status
        );
        $response = $this->postJson('api/withdraws/agent/' .   $withdrawCreate->id . '/cancel');
        $response->assertBadRequest();
    }

    public function test_agent_transaction_id_is_saved_to_database_altogether_with_withdraw_creation(): void
    {
        $agent_transaction_id = Str::random(64);
        $this->postJson('api/withdraws/agent', [
            'code' => $this->user->code,
            'to' => $this->to_wallet,
            'amount' => rand(2, 5),
            'agent_transaction_id' => $agent_transaction_id,
        ]);

        $withdraw = Withdraw::where('agent_transaction_id', $agent_transaction_id)->first();
        $this->assertNotNull($withdraw);
    }
}
