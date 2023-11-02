<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use App\Models\Admin;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function setUp(): void
    {
        parent::setUp();
        $this->withHeaders([
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
            'Authorization' =>  'Bearer ' . $this->getToken()
        ]);
    }

    public function test_admin_can_create_new_wallet(): void
    {
        $response = $this->postJson('api/wallets');
        $response->assertStatus(200);
        $this->assertDatabaseCount('wallets', 1);
    }

    public function test_admin_can_view_wallet(): void
    {
        $postResponse = $this->postJson('api/wallets');
        $response = $this->getJson('api/wallets');
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json()['data']);
    }

    public function test_admin_can_find_wallet(): void
    {
        $walletResponse = $this->postJson('api/wallets')->json(); // Not work need deposit confirm
        $response = $this->getJson('api/wallets/' . $walletResponse['wallet']['id'])->json();
        $this->assertNotEmpty($response['wallet']);
    }

    public function test_admin_can_activate_wallet(): void
    {
        $walletResponse = $this->postJson('api/wallets')->json(); // Not work need deposit confirm
        $response = $this->postJson('api/wallets/' . $walletResponse['wallet']['id'] . '/activate')->json();
        $this->assertNotNull($response['wallet']['activated_at']);
    }

    function getToken()
    {
        // Login
        $adminPassword = Str::random(6);
        $this->admin = Admin::factory(['password' => bcrypt($adminPassword)])->create();
        $responseAuth = $this->postJson('api/admin/login', [
            'name' => $this->admin->name,
            'password' => $adminPassword
        ])->json();

        return $responseAuth['token'];
    }
}
