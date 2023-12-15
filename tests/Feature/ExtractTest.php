<?php

namespace Tests\Feature;

use App\Enums\ExtractStatus;
use App\Enums\ExtractType;
use App\Enums\ResponseStatus;
use App\Jobs\ProcessConfirmedExtract;
use App\Models\Agent;
use App\Models\Extract;
use App\Models\Wallet;
use App\Services\Tron;
use Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Queue;
use Str;
use Tests\TestCase;

class ExtractTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    private $wallet;
    private $to_wallet;
    private $agent;

    public function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->seed();
        $this->agent = Agent::first();
        $this->wallet = Wallet::first();
        $this->to_wallet = 'TWxQ5m1TMLumFH7bMws4Q1qoP1FeYfkhKc';
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
        Http::fake([
            config('app')['tron_api_url'] . '/wallet/createtransaction' => Http::response([
                "visible" => true,
                "txID" => bin2hex(random_bytes(32))
            ])
        ]);

        Http::fake([
            config('app')['tron_api_url'] . '/wallet/broadcasttransaction' => Http::response([
                "result" => true,
                "txid" => Str::random(64)
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
    }

    public function test_agent_can_find_extract(): void
    {
        Extract::factory()->count(5)->for($this->agent)->for(Wallet::factory()->for($this->agent)->create())->create();
        $extract = Extract::first();
        $response = $this->getJson('api/extracts/agent/' . $extract->id);
        $response->assertOk();
        $this->assertArrayHasKey('extract', $response->json());
        $this->assertNotEmpty($response->json()['extract']);
    }

    public function test_agent_can_extract_only_if_from_wallet_and_to_wallet_address_are_not_equal(): void
    {
        $from_wallet = $this->wallet;
        $from_wallet->update(['trx' => rand(5, 10) * Tron::DIGITS]);
        $this->postJson('api/extracts/agent', [
            'amount' => rand(1, 5),
            'to' => $from_wallet->base58_check,
            'type' => ExtractType::TRX->value,
            'wallet_id' => $from_wallet->id,
            'agent_transaction_id' => fake()->unique()->numberBetween(1, 10)
        ])->assertStatus(ResponseStatus::UNPROCESSABLE_ENTITY->value);

        $this->assertDatabaseCount('extracts', 0);

        $this->postJson('api/extracts/agent', [
            'amount' => rand(1, 5),
            'to' => $this->to_wallet,
            'type' => ExtractType::TRX->value,
            'wallet_id' => $from_wallet->id,
            'agent_transaction_id' => fake()->unique()->numberBetween(1, 10)
        ])->assertOk();

        $this->assertDatabaseCount('extracts', 1);
    }

    public function test_agent_can_extract_TRX_only_if_trx_balance_amount_is_greather_than_extracted_trx_amount(): void
    {
        $wallet = $this->wallet;
        $wallet->update(['trx' => rand(5, 10) * Tron::DIGITS]);
        $this->postJson('api/extracts/agent', [
            'amount' => rand(20, 30),
            'to' => $this->to_wallet,
            'type' => ExtractType::TRX->value,
            'wallet_id' => $wallet->id,
            'agent_transaction_id' => fake()->unique()->numberBetween(1, 10)
        ])->assertStatus(ResponseStatus::UNPROCESSABLE_ENTITY->value);

        $this->assertDatabaseCount('extracts', 0);

        $this->postJson('api/extracts/agent', [
            'amount' => rand(2, 5),
            'to' => $this->to_wallet,
            'type' => ExtractType::TRX->value,
            'wallet_id' => $wallet->id,
            'agent_transaction_id' => fake()->unique()->numberBetween(1, 10)
        ])->assertOk();

        $this->assertDatabaseCount('extracts', 1);
    }

    public function test_agent_can_extract_usdt_only_if_usdt_balance_amount_is_greather_than_extracted_usdt_amount(): void
    {
        $wallet = $this->wallet;
        $wallet->update(['balance' => rand(5, 10) * Tron::DIGITS]);
        $this->postJson('api/extracts/agent', [
            'amount' => rand(20, 30),
            'to' => $this->to_wallet,
            'type' => ExtractType::USDT->value,
            'wallet_id' => $wallet->id,
            'agent_transaction_id' => fake()->unique()->numberBetween(1, 10)
        ])->assertStatus(ResponseStatus::UNPROCESSABLE_ENTITY->value);

        $this->assertDatabaseCount('extracts', 0);

        $this->postJson('api/extracts/agent', [
            'amount' => rand(2, 5),
            'to' => $this->to_wallet,
            'type' => ExtractType::USDT->value,
            'wallet_id' => $wallet->id,
            'agent_transaction_id' => fake()->unique()->numberBetween(1, 10)
        ])->assertOk();

        $this->assertDatabaseCount('extracts', 1);
    }

    public function test_agent_can_confirm_extract_if_extracting_usdt_or_trx_is_success(): void
    {
        $wallet = $this->wallet;
        $wallet->update(['balance' => rand(5, 10) * Tron::DIGITS, 'trx' => rand(5, 10) * Tron::DIGITS]);
        $this->postJson('api/extracts/agent', [
            'amount' => rand(20, 30),
            'to' => $this->to_wallet,
            'type' => rand(1, 2),
            'wallet_id' => $wallet->id,
            'agent_transaction_id' => fake()->unique()->numberBetween(1, 10)
        ])->assertStatus(ResponseStatus::UNPROCESSABLE_ENTITY->value);

        $this->assertDatabaseCount('extracts', 0);

        $response = $this->postJson('api/extracts/agent', [
            'amount' => rand(2, 5),
            'to' => $this->to_wallet,
            'type' =>  rand(ExtractType::USDT->value, ExtractType::TRX->value),
            'wallet_id' => $wallet->id,
            'agent_transaction_id' => fake()->unique()->numberBetween(1, 10)
        ]);

        $response->assertOk();
        $this->assertEquals(ExtractStatus::CONFIRMED->value, $response->json()['extract']['status']);

        $extractId = $response->json()['extract']['id'];

        Queue::assertPushed(function (ProcessConfirmedExtract $job) use ($extractId) {
            return $job->extractId === $extractId;
        });
    }

    public function test_agent_transaction_id_is_saved_to_database_altogether_with_extract_creation(): void
    {
        $agent_transaction_id = Str::random(64);
        $from_wallet = $this->wallet;
        $from_wallet->update(['trx' => rand(5, 10) * Tron::DIGITS]);
        $this->postJson('api/extracts/agent', [
            'amount' => rand(1, 5),
            'to' => $this->to_wallet,
            'type' => ExtractType::TRX->value,
            'wallet_id' => $from_wallet->id,
            'agent_transaction_id' => $agent_transaction_id
        ])->assertOk();

        $extract = Extract::where('agent_transaction_id', $agent_transaction_id)->first();
        $this->assertNotNull($extract);
    }
}
