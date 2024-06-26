<?php

namespace Tests\Feature;

use App\Enums\DepositStatus;
use App\Enums\ResponseStatus;
use App\Jobs\ProcessConfirmedDeposit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Models\Agent;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Deposit;
use Illuminate\Support\Facades\Queue;

class DepositTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    private $agent;
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
        Queue::fake();
    }

    /**
     * Agent deposit
     */
    public function test_agent_can_create_deposit(): void
    {
        $response = $this->postJson('api/deposits/agent', [
            'code' => Str::random('3'),
            'name' => $this->faker()->lastName(),
            'amount' => rand(1, 5),
            'agent_transaction_id' => Str::random(64),
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('deposits', 1);
        $this->assertArrayHasKey('wallet', $response->json());
        $this->assertArrayHasKey('deposit', $response->json());
    }

    public function test_newly_created_deposit_status_is_default_to_pending(): void
    {
        $response = $this->postJson('api/deposits/agent', [
            'code' => Str::random('3'),
            'name' => $this->faker()->lastName(),
            'amount' => rand(1, 5),
            'agent_transaction_id' => Str::random(64),
        ]);

        $this->assertEquals(
            DepositStatus::PENDING->value,
            Deposit::find($response->json()['deposit']['id'])->status
        );
    }

    public function test_user_is_created_altogether_with_depoist_creation_if_user_do_not_exit(): void
    {
        $code = Str::random('3');
        $existedUser = User::where('code', $code)->first();

        $responseData = $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $this->faker()->lastName(),
            'amount' => rand(1, 5),
            'agent_transaction_id' => Str::random(64),
        ])->json();

        $this->assertDatabaseCount('users', 1);

        $existedUser = User::where('code', $code)->first();
        $this->assertNotNull($existedUser);

        $response = $this->postJson('api/deposits/agent/' . $responseData['deposit']['id'] . '/cancel');

        $response->assertOk();

        $response = $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $this->faker()->lastName(),
            'amount' => rand(1, 5),
            'agent_transaction_id' => Str::random(64),
        ]);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_agent_user_can_deposit_only_if_avaliable_wallet_exists(): void
    {
        //todo: make a unit test for Wallet::findAvailable()
        while (Wallet::findAvailable($this->agent->id ,1) != null) {
            $response = $this->postJson('api/deposits/agent', [
                'code' => $this->faker()->unique()->randomNumber(3),
                'name' => $this->faker()->lastName(),
                'amount' => 1,
                'agent_transaction_id' => Str::random(64),
            ]);
            $response->assertOk();
        }

        $response = $this->postJson('api/deposits/agent', [
            'code' => $this->faker()->unique()->randomNumber(3),
            'name' => $this->faker()->lastName(),
            'amount' => 1,
            'agent_transaction_id' => Str::random(64),
        ]);
        $response->assertBadRequest();
    }

    public function test_agent_user_cannot_depoist_again_if_existing_deposit_is_pending_or_confirmed_with_the_same_deposit_amount(): void
    {
        $code = Str::random('3');
        $name =  $this->faker()->lastName();
        $amount = rand(1, 5);
        $response = $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $name,
            'amount' => $amount,
            'agent_transaction_id' => Str::random(64),
        ])->assertOk();

        $depositId = $response->json()['deposit']['id'];

        $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $name,
            'amount' => $amount,
            'agent_transaction_id' => Str::random(64),
        ])->assertUnprocessable();

        $this->postJson('api/deposits/agent/' . $depositId . '/confirm')->assertOk();

        Queue::assertPushed(function (ProcessConfirmedDeposit $job) use ($depositId) {
            return $job->depositId === $depositId;
        });

        Deposit::where('id', $depositId)->update(['status' => DepositStatus::COMPLETED->value]);

        $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $name,
            'amount' => $amount,
            'agent_transaction_id' => Str::random(64),
        ])->assertOk();

        $response = $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $name,
            'amount' => rand(6, 10),
            'agent_transaction_id' => Str::random(64),
        ])->assertOk();
    }

    public function test_agent_user_cannot_deposit_over_three_times_if_existing_deposit_is_pending_or_confirmed_and_deposit_amounts_are_different(): void
    {
        $code = Str::random('3');
        $name =  $this->faker()->lastName();
        $amount = rand(1, 5);
        $firstDeposit = $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $name,
            'amount' => $amount,
            'agent_transaction_id' => Str::random(64),
        ])->assertOk();

        $firstDepositId = $firstDeposit->json()['deposit']['id'];

        $secondDeposit = $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $name,
            'amount' => rand(6, 10),
            'agent_transaction_id' => Str::random(64),
        ])->assertOk();
        $secondDepositId = $secondDeposit->json()['deposit']['id'];

        $thirdDeposit = $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $name,
            'amount' => rand(11, 15),
            'agent_transaction_id' => Str::random(64),
        ])->assertOk();

        $fourthDeposit = $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $name,
            'amount' => rand(12, 20),
            'agent_transaction_id' => Str::random(64),
        ])->assertUnprocessable();

        $this->postJson('api/deposits/agent/' . $firstDepositId . '/cancel')->assertOk();

        $response = $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $name,
            'amount' => $amount,
            'agent_transaction_id' => Str::random(64),
        ])->assertOk();

        $this->postJson('api/deposits/agent/' . $secondDepositId . '/cancel')->assertOk();

        $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $name,
            'amount' => $amount,
            'agent_transaction_id' => Str::random(64),
        ])->assertUnprocessable();
    }

    public function test_agent_user_confirm_deposit(): void
    {
        $response = $this->postJson('api/deposits/agent', [
            'code' => Str::random('3'),
            'name' => $this->faker()->lastName(),
            'amount' => rand(1, 5),
            'agent_transaction_id' => Str::random(64),
        ]);
        $this->assertEquals(
            DepositStatus::PENDING->value,
            Deposit::find($response->json()['deposit']['id'])->status
        );
        $confirmResponse = $this->postJson('api/deposits/agent/' . $response->json()['deposit']['id'] . '/confirm');

        $confirmResponse->assertStatus(200);

        $this->assertEquals(
            DepositStatus::CONFIRMED->value,
            $confirmResponse->json()['deposit']['status']
        );
    }

    public function test_only_pending_deposit_can_be_confirmed(): void
    {
        $response = $this->postJson('api/deposits/agent', [
            'code' => Str::random('3'),
            'name' => $this->faker()->lastName(),
            'amount' => rand(1, 5),
            'agent_transaction_id' => Str::random(64),
        ]);

        $confirmResponse = $this->postJson('api/deposits/agent/' . $response->json()['deposit']['id'] . '/confirm');

        $confirmResponse->assertStatus(200);

        $this->assertNotEquals(
            DepositStatus::PENDING->value,
            Deposit::find($response->json()['deposit']['id'])->status
        );

        $response = $this->postJson('api/deposits/agent/' . $response->json()['deposit']['id'] . '/confirm');

        $response->assertBadRequest();
    }

    public function test_agent_user_can_list_deposits(): void
    {
        Deposit::factory()->count(5)->for(User::factory()->for($this->agent)->create())->for(Wallet::factory()->for($this->agent)->create())->create();
        $response = $this->getJson('api/deposits/agent');

        $response->assertOk();
        $this->assertArrayHasKey('data', $response->json());
        $this->assertNotNull($response->json()['data']);
    }

    public function test_agent_user_cancel_deposit(): void
    {
        $response = $this->postJson('api/deposits/agent', [
            'code' => Str::random('3'),
            'name' => $this->faker()->lastName(),
            'amount' => rand(1, 5),
            'agent_transaction_id' => Str::random(64),
        ]);

        $this->assertEquals(
            DepositStatus::PENDING->value,
            Deposit::find($response->json()['deposit']['id'])->status
        );

        $cancelResponse = $this->postJson('api/deposits/agent/' . $response->json()['deposit']['id'] . '/cancel');

        $cancelResponse->assertOk();

        $this->assertEquals(
            DepositStatus::CANCELED->value,
            Deposit::find($response->json()['deposit']['id'])->status
        );
    }

    public function test_only_pending_deposit_can_be_cancelled(): void
    {
        $response = $this->postJson('api/deposits/agent', [
            'code' => Str::random('3'),
            'name' => $this->faker()->lastName(),
            'amount' => rand(1, 5),
            'agent_transaction_id' => Str::random(64),
        ]);

        $cancelResponse = $this->postJson('api/deposits/agent/' . $response->json()['deposit']['id'] . '/cancel');

        $cancelResponse->assertOk();

        $this->assertNotEquals(
            DepositStatus::PENDING->value,
            Deposit::find($response->json()['deposit']['id'])->status
        );

        $response = $this->postJson('api/deposits/agent/' . $response->json()['deposit']['id'] . '/cancel');

        $response->assertBadRequest();
    }

    public function test_agent_transaction_id_is_saved_to_database_altogether_with_deposit_creation(): void
    {
        $agent_transaction_id = Str::random(64);
        $this->postJson('api/deposits/agent', [
            'code' => Str::random('3'),
            'name' => $this->faker()->lastName(),
            'amount' => rand(1, 5),
            'agent_transaction_id' => $agent_transaction_id,
        ]);

        $deposit = Deposit::where('agent_transaction_id', $agent_transaction_id)->first();
        $this->assertNotNull($deposit);
    }
}
