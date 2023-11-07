<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Admin;
use App\Models\Wallet;
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
        $wallet = Wallet::first();
        $wallet->update(['activated_at' => null]);

        $response = $this->postJson('api/wallets/' . $wallet->id . '/activate');
        $response->assertOk();

        $this->assertArrayHasKey('wallet', $response->json());
        $this->assertNotNull($response->json()['wallet']['activated_at']);
    }

    public function test_admin_can_find_wallet(): void
    {
        $wallet = Wallet::first();
        $this->postJson('api/wallets/' . $wallet->id . '/activate')->assertOk();

        $response = $this->getJson('api/wallets/' . $wallet->id);
        $this->assertArrayHasKey('wallet', $response->json());
    }
}
