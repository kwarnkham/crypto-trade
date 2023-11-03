<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use App\Models\Transaction;
use App\Models\Admin;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    private $admin;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withHeaders([
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
            'Authorization' =>  'Bearer ' . $this->getToken()
        ]);
    }

    public function test_admin_can_view_transactions(): void
    {
        $transactions = Transaction::factory()->count(5)->create();
        $response = $this->getJson('api/transactions');
        $response->assertStatus(200);
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
