<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessConfirmedWithdraw;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Enums\WithdrawStatus;
use App\Models\Agent;
use App\Models\Admin;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdraw;
use Tests\TestCase;

class WithdrawTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    private $wallet;
    private $agent;
    private $user;
    private $admin;

    public function setUp(): void
    {
        parent::setUp();
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Queue::fake();
        $this->seed();
        $this->actingAs(Admin::first());
        $this->agent = Agent::first();
        $this->wallet = Wallet::first();
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
    }

    public function tearDown(): void
    {
        // Re-enable foreign key checks after the test
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        parent::tearDown();
    }

    public function test_agent_can_create_withdraw(): void
    {
        $response = $this->postJson('api/withdraws/agent', [
            'code' => $this->user->code,
            'to' => $this->wallet->base58_check,
            'amount' => rand(2, 5)
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
            'amount' => rand(2, 5)
        ]);

        $response->assertBadRequest();
    }

    public function test_agent_user_can_withdraw_only_if_balance_amount_is_greather_than_withdraw_amount(): void
    {
        $this->postJson('api/withdraws/agent', [
            'code' => $this->user->code,
            'to' => $this->wallet->base58_check,
            'amount' => rand(6, 10)
        ])->assertBadRequest();
        $this->assertDatabaseCount('withdraws', 0);


        $this->postJson('api/withdraws/agent', [
            'code' => $this->user->code,
            'to' => $this->wallet->base58_check,
            'amount' => rand(2, 5)
        ])->assertOk();
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

    public function test_agent_user_can_confirm_withdraw(): void
    {
        $withdrawWallet = Wallet::latest('id')->first();
        $response = $this->postJson('api/withdraws/agent', [
            'code' => $this->user->code,
            'to' => $withdrawWallet->base58_check,
            'amount' => rand(2, 5)
        ]);

        $withdrawId = $response->json()['withdraw']['id'];

        $this->postJson('api/withdraws/agent/' . $withdrawId . '/confirm')->assertOk();
        Queue::assertPushed(function (ProcessConfirmedWithdraw $job) use ($withdrawId) {
            return $job->withdrawId === $withdrawId;
        });
    }

    public function test_withdraw_can_be_confirmed_only_if_withdraw_status_is_pending(): void
    {
        $withdrawWallet = Wallet::latest('id')->first();
        $response = $this->postJson('api/withdraws/agent', [
            'code' => $this->user->code,
            'to' => $withdrawWallet->base58_check,
            'amount' => rand(2, 5)
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
        Withdraw::factory()->count(5)->create();
        $response = $this->getJson('api/withdraws/agent');
        $response->assertOk();
        $this->assertArrayHasKey('data', $response->json());
        $this->assertNotEmpty($response->json()['data']);
    }

    public function test_admin_can_list_withdraws(): void
    {
        Withdraw::factory()->count(5)->create();
        $response = $this->getJson('api/withdraws');
        $response->assertOk();
        $this->assertArrayHasKey('data', $response->json());
        $this->assertNotEmpty($response->json()['data']);
    }

    public function test_agent_user_can_cancel_withdraw(): void
    {
        $withdrawCreate = Withdraw::factory()->create();

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
        $withdrawCreate = Withdraw::factory()->create();
        $cancelResponse = $this->postJson('api/withdraws/agent/' .  $withdrawCreate->id . '/cancel');
        $cancelResponse->assertOk();

        $this->assertNotEquals(
            withdrawstatus::PENDING->value,
            $withdrawCreate->refresh()->status
        );
        $response = $this->postJson('api/withdraws/agent/' .   $withdrawCreate->id . '/cancel');
        $response->assertBadRequest();
    }
}
