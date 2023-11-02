<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use App\Enums\WithdrawStatus;
use App\Models\Agent;
use App\Models\Admin;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdraw;
use App\Models\Transaction;
use Tests\TestCase;

class WithdrawTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    private $wallet;
    private $agent;
    private $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->agent = Agent::query()->first();
        $this->wallet = Wallet::query()->first();
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
            'Accept' => 'application/json',
            'Authorization' =>  'Bearer ' . $this->getToken()
        ]);
    }


    public function test_agent_can_create_withdraw(): void
    {
        // $depositResponse = $this->postJson('api/deposits/agent', [
        //     'code' => $this->user->code,
        //     'name' => $this->faker()->lastName(),
        //     'amount' => rand(1, 5)
        // ]);
        // $depositResponse->assertStatus(200);
        // $confirmResponse = $this->postJson('api/deposits/agent/' . $depositResponse->json()['deposit']['id'] . '/confirm');
        // $confirmResponse->assertStatus(200);

        $response = $this->postJson('api/withdraws/agent', [
            'code' => $this->user->code,
            'to' => $this->wallet->base58_check,
            'amount' => rand(2, 5)
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('withdraws', 1);
    }

    public function test_invalid_wallet_address_cant_be_withdrawed(): void
    {
        $response = $this->postJson('api/withdraws/agent', [
            'code' => $this->user->code,
            'to' => 'A' . $this->wallet->base58_check,
            'amount' => rand(2, 5)
        ]);

        $response->assertStatus(400);
    }

    public function test_user_balance_amount_is_checked_to_be_greather_than_withdraw_amount(): void
    {
        $response = $this->postJson('api/withdraws/agent', [
            'code' => $this->user->code,
            'to' => $this->wallet->base58_check,
            'amount' => rand(6, 10)
        ]);
        $response->assertStatus(400);
        $this->assertDatabaseCount('withdraws', 0);


        $response = $this->postJson('api/withdraws/agent', [
            'code' => $this->user->code,
            'to' => $this->wallet->base58_check,
            'amount' => rand(2, 5)
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseCount('withdraws', 1);
    }

    public function test_newly_created_withdraw_status_is_default_to_pending(): void
    {
        $response = $this->postJson('api/withdraws/agent', [
            'code' => $this->user->code,
            'to' => $this->wallet->base58_check,
            'amount' => rand(2, 5)
        ]);

        $this->assertEquals(
            WithdrawStatus::PENDING->value,
            Withdraw::find($response->json()['withdraw']['id'])->status
        );
    }

    public function test_agent_user_can_confirm_withdraw(): void //Not work
    {
        $response = $this->postJson('api/withdraws/agent', [
            'code' => $this->user->code,
            'to' => $this->wallet->base58_check,
            'amount' => rand(2, 5)
        ]);
        $confirmResponse = $this->postJson('api/withdraws/agent/' . $response->json()['withdraw']['id'] . '/confirm');
        $confirmResponse->assertStatus(200);
    }

    public function test_withdraw_cant_be_confirmed_if_withdraw_status_is_not_pending(): void
    {
        $withdrawCreate = Withdraw::factory(['status' => 2])->create();
        $this->assertNotEquals(
            WithdrawStatus::PENDING->value,
            $withdrawCreate->status
        );
        $response = $this->postJson('api/withdraws/agent/' . $withdrawCreate->id . '/confirm');
        $response->assertStatus(400);
    }


    public function test_agent_user_can_view_withdraw(): void
    {
        $withdrawCreate = Withdraw::factory()->count(5)->create();
        $response = $this->getJson('api/withdraws/agent');
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json()['data']);
    }

    public function test_admin_can_view_withdraw(): void
    {
        $withdrawCreate = Withdraw::factory()->count(5)->create();
        $response = $this->getJson('api/withdraws');
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json()['data']);
    }

    public function test_agent_user_cancel_withdraw(): void
    {
        $withdrawCreate = Withdraw::factory()->create();

        $this->assertEquals(
            WithdrawStatus::PENDING->value,
            $withdrawCreate->status
        );

        $cancelResponse = $this->postJson('api/withdraws/agent/' . $withdrawCreate->id . '/cancel');
        $cancelResponse->assertStatus(200);
    }

    public function test_only_pending_deposit_can_be_cancelled(): void
    {
        $withdrawCreate = Withdraw::factory()->create();
        $cancelResponse = $this->postJson('api/withdraws/agent/' .  $withdrawCreate->id . '/cancel');
        $cancelResponse->assertStatus(200);

        $this->assertNotEquals(
            withdrawstatus::PENDING->value,
            $cancelResponse->json()['withdraw']['status']
        );
        $response = $this->postJson('api/withdraws/agent/' .   $cancelResponse->json()['withdraw']['id'] . '/cancel');
        $response->assertStatus(400);
    }

    function getToken()
    {
        // Admin Login
        $adminPassword = Str::random(6);
        $this->admin = Admin::factory(['password' => bcrypt($adminPassword)])->create();
        $responseAuth = $this->postJson('api/admin/login', [
            'name' => $this->admin->name,
            'password' => $adminPassword
        ])->json();

        return $responseAuth['token'];
    }
}
