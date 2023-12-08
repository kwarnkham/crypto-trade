<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use App\Models\Admin;
use App\Models\Agent;
use App\Models\Wallet;
use App\Services\Tron;
use Str;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    private $admin;
    private $agent;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->agent = Agent::first();
        $this->withHeaders([
            'x-agent'   => $this->agent->name,
            'x-api-key' => $this->agent->jwtKey(),
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json'
        ]);
        $this->actingAs(Admin::first());
        Http::preventStrayRequests();
    }

    public function test_admin_can_create_new_wallet(): void
    {
        $response = $this->postJson('api/wallets', [
            'agent_id' => $this->agent->id
        ]);
        $response->assertOk();
        $this->assertArrayHasKey('wallet', $response->json());
        $this->assertNotNull('wallets', Wallet::where('id', $response->json()['wallet']['id'])->first());
    }

    public function test_admin_can_list_wallets(): void
    {
        $response = $this->getJson('api/wallets')->assertOk();
        $this->assertArrayHasKey('data', $response->json());
        $this->assertNotEmpty($response->json()['data']);
    }

    public function test_admin_can_activate_wallet(): void
    {
        Http::fake([
            config('app')['tron_api_url'] . '/v1/accounts/*' => Http::response(['data' => [
                [
                    "balance" => rand(1, 5) * Tron::DIGITS,
                    "address" => Str::random(42),
                    "create_time" => now(),
                    "trc20" => [[Str::random(64) => rand(1, 5) * Tron::DIGITS]],
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

        $wallet = Wallet::first();
        $wallet->update(['activated_at' => null]);

        $response = $this->postJson('api/wallets/' . $wallet->id . '/activate');
        $response->assertOk();

        $this->assertArrayHasKey('wallet', $response->json());
        $this->assertNotNull($response->json()['wallet']['activated_at']);
    }

    public function test_admin_can_find_wallet(): void
    {

        Http::fake([
            config('app')['tron_api_url'] . '/v1/accounts/*' => Http::response(['data' => [
                [
                    "balance" => rand(1, 5) * Tron::DIGITS,
                    "address" => Str::random(42),
                    "create_time" => now(),
                    "trc20" => [[Str::random(64) => rand(1, 5) * Tron::DIGITS]],
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

        $wallet = Wallet::first();
        $this->postJson('api/wallets/' . $wallet->id . '/activate')->assertOk();

        $response = $this->getJson('api/wallets/' . $wallet->id);
        $this->assertArrayHasKey('wallet', $response->json());
    }

    public function test_wallet_address_is_valid(): void
    {
        $wallet = Wallet::first();

        Http::fake([
            config('app')['tron_api_url'] . '/wallet/validateaddress' => function ($request) use ($wallet) {
                $requestData = $request->data();
                if ($requestData['address'] == $wallet->base58_check) {
                    return Http::response([
                        'message' => Str::random(30),
                        'result' => true,
                    ]);
                } else {
                    return Http::response([
                        'message' => Str::random(30),
                        'result' => false,
                    ]);
                }

                return Http::response([], 404);
            }
        ]);

        // Valid address test
        $response = $this->postJson('api/wallets/agent/validate-address', [
            'wallet_address' => $wallet->base58_check,
        ])->assertOk();

        $response->assertJson(['result' => true]);

        // Invalid address test
        $response = $this->postJson('api/wallets/agent/validate-address', [
            'wallet_address' => Str::random(30),
        ])->assertOk();

        $response->assertJson(['result' => false]);
    }
}
