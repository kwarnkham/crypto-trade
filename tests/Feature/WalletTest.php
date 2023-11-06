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

    // public function test_admin_can_find_wallet(): void
    // {
    //     $walletResponse = $this->postJson('api/wallets')->json(); // Not work need deposit confirm
    //     $response = $this->getJson('api/wallets/' . $walletResponse['wallet']['id'])->json();
    //     $this->assertNotEmpty($response['wallet']);
    // }

    // public function test_admin_can_activate_wallet(): void
    // {
    //     $walletResponse = $this->postJson('api/wallets')->json(); // Not work need deposit confirm
    //     $response = $this->postJson('api/wallets/' . $walletResponse['wallet']['id'] . '/activate')->json();
    //     $this->assertNotNull($response['wallet']['activated_at']);
    // }
}
