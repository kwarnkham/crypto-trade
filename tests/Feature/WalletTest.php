<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use App\Models\Admin;
use App\Models\Wallet;
use App\Services\Tron;
use Str;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    private $admin;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAs(Admin::first());
        Http::preventStrayRequests();
    }

    public function test_admin_can_create_new_wallet(): void
    {
        $response = $this->postJson('api/wallets');
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
            config('app')['tron_api_url'].'/v1/accounts/*' => Http::response(['data' => [
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
            config('app')['tron_api_url'].'/wallet/getaccountresource' => Http::response([
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
            config('app')['tron_api_url'].'/v1/accounts/*' => Http::response(['data' => [
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
            config('app')['tron_api_url'].'/wallet/getaccountresource' => Http::response([
                "freeNetUsed" => rand(500, 600),
                "freeNetLimit" => rand(600, 700),
            ])
        ]);

        $wallet = Wallet::first();
        $this->postJson('api/wallets/' . $wallet->id . '/activate')->assertOk();

        $response = $this->getJson('api/wallets/' . $wallet->id);
        $this->assertArrayHasKey('wallet', $response->json());
    }
}
