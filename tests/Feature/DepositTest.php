<?php

namespace Tests\Feature;

use App\Enums\DepositStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Models\Agent;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Deposit;

class DepositTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    private $agent;
    private $user;
    private $wallet;
    private $deposit;
    private $deposit_id;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->agent = Agent::query()->first();
        $this->withHeaders([
            'x-agent'   => $this->agent->name,
            'x-api-key' => $this->agent->jwtKey(),
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json'
        ]);
    }

    /**
     * Agent deposit
     */
    public function test_agent_can_create_deposit(): void
    {
        $response = $this->postJson('api/deposits/agent', [
            'code' => Str::random('3'),
            'name' => $this->faker()->lastName(),
            'amount' => rand(1, 5)
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('deposits', 1);
    }

    public function test_newly_created_deposit_status_is_default_to_pending(): void
    {
        $response = $this->postJson('api/deposits/agent', [
            'code' => Str::random('3'),
            'name' => $this->faker()->lastName(),
            'amount' => rand(1, 5)
        ]);

        $this->assertEquals(
            DepositStatus::PENDING->value,
            Deposit::find($response->json()['id'])->status
        );
    }

    public function test_user_is_created_altogether_with_depoist_creation_if_user_do_not_exit(): void
    {
        $code = Str::random('3');
        $existedUser = User::where('code', $code)->first();

        $responseData = $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $this->faker()->lastName(),
            'amount' => rand(1, 5)
        ])->json();

        $this->assertDatabaseCount('users', 1);

        $existedUser = User::where('code', $code)->first();
        $this->assertNotNull($existedUser);

        $response = $this->postJson('api/deposits/agent/' . $responseData['id'] . '/cancel');

        $response->assertOk();

        $response = $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $this->faker()->lastName(),
            'amount' => rand(1, 5)
        ]);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_agent_cant_create_if_pendind_deposit_exist(): void
    {
        $code = Str::random('3');
        $name =  $this->faker()->lastName();
        $response = $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $name,
            'amount' => rand(1, 5)
        ]);

        $this->assertEquals(
            DepositStatus::PENDING->value,
            Deposit::find($response->json()['id'])->status
        );

        $response = $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $name,
            'amount' => rand(1, 5)
        ]);
        $response->assertStatus(400);
    }

    public function test_agent_user_can_deposit_if_avaliable_wallet_exist(): void
    {
        $code = Str::random('3');
        $name =  $this->faker()->lastName();
        $existAvaliableWallet = Wallet::findAvailable();
        $this->assertNotNull($existAvaliableWallet);
        $response = $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $name,
            'amount' => rand(1, 5)
        ]);
        $response->assertStatus(200);
    }

    public function test_agent_user_cant_deposit_if_wallet_is_not_activate(): void
    {
        $code = Str::random('3');
        $name =  $this->faker()->lastName();
        $checkAvaliableWallet = Wallet::findAvailable();
        $this->assertNotNull($checkAvaliableWallet);
        $response = $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $name,
            'amount' => rand(1, 5)
        ]);
        $response->assertStatus(200);

        $deactivateSecondWalletFromDbSeed = Wallet::where('id', '!=', $checkAvaliableWallet->id)->update(['activated_at' => null]);
        $checkAvaliableActivatedWallet = Wallet::findAvailable();
        $this->assertNull($checkAvaliableActivatedWallet);

        $response = $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $name,
            'amount' => rand(1, 5)
        ]);
        $response->assertStatus(400);
    }

    public function test_agent_user_cant_deposit_if_wallet_has_pending_or_confirmed_deposits(): void
    {
        $code = Str::random('3');
        $name =  $this->faker()->lastName();
        $checkAvaliableWallet = Wallet::findAvailable();
        $this->assertNotNull($checkAvaliableWallet);
        $response = $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $name,
            'amount' => rand(1, 5)
        ]);
        $response->assertStatus(200);

        $deleteSecondWalletFromDbSeed = Wallet::where('id', '!=', $checkAvaliableWallet->id)->delete();
        $checkAvaliableWalletDepositStatus = Wallet::findAvailable();
        $this->assertNull($checkAvaliableWalletDepositStatus);
        $response = $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $name,
            'amount' => rand(1, 5)
        ]);
        $response->assertStatus(400);
    }

    public function test_agent_user_confirm_deposit(): void
    {
        $response = $this->postJson('api/deposits/agent', [
            'code' => Str::random('3'),
            'name' => $this->faker()->lastName(),
            'amount' => rand(1, 5)
        ]);
        $this->assertEquals(
            DepositStatus::PENDING->value,
            Deposit::find($response->json()['id'])->status
        );
        $confirmResponse = $this->postJson('api/deposits/agent/' . $response->json()['id'] . '/confirm');
        $confirmResponse->assertStatus(200);
    }

    public function test_only_pending_deposit_can_be_confirmed(): void
    {
        $response = $this->postJson('api/deposits/agent', [
            'code' => Str::random('3'),
            'name' => $this->faker()->lastName(),
            'amount' => rand(1, 5)
        ]);
        $confirmResponse = $this->postJson('api/deposits/agent/' . $response->json()['id'] . '/confirm');
        $confirmResponse->assertStatus(200);
        $this->assertNotEquals(
            DepositStatus::PENDING->value,
            Deposit::find($response->json()['id'])->status
        );
        $response = $this->postJson('api/deposits/agent/' . $response->json()['id'] . '/confirm');
        $response->assertStatus(400);
    }

    public function test_agent_user_view_deposit(): void
    {
        $response = $this->getJson('api/deposits/agent');

        $response->assertStatus(200);
    }

    public function test_agent_user_cancel_deposit(): void
    {
        $response = $this->postJson('api/deposits/agent', [
            'code' => Str::random('3'),
            'name' => $this->faker()->lastName(),
            'amount' => rand(1, 5)
        ]);
        $this->assertEquals(
            DepositStatus::PENDING->value,
            Deposit::find($response->json()['id'])->status
        );

        $cancelResponse = $this->postJson('api/deposits/agent/' . $response->json()['id'] . '/cancel');
        $cancelResponse->assertStatus(200);
    }

    public function test_only_pending_deposit_can_be_cancelled(): void
    {
        $response = $this->postJson('api/deposits/agent', [
            'code' => Str::random('3'),
            'name' => $this->faker()->lastName(),
            'amount' => rand(1, 5)
        ]);
        $cancelResponse = $this->postJson('api/deposits/agent/' . $response->json()['id'] . '/cancel');
        $cancelResponse->assertStatus(200);

        $this->assertNotEquals(
            DepositStatus::PENDING->value,
            Deposit::find($response->json()['id'])->status
        );
        $response = $this->postJson('api/deposits/agent/' . $response->json()['id'] . '/cancel');
        $response->assertStatus(400);
    }
}
