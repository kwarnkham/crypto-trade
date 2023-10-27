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
            // 'Authorization' => 'Bearer ' . $this->getBearerToken(),
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
            'amount' => rand(1, 5)
        ])->json();

        $this->assertDatabaseCount('users', 1);

        $existedUser = User::where('code', $code)->first();
        $this->assertNotNull($existedUser);

        $response = $this->postJson('api/deposits/agent/' . $responseData['depoist']['id'] . '/cancel');

        $response->assertOk();

        $response = $this->postJson('api/deposits/agent', [
            'code' => $code,
            'name' => $this->faker()->lastName(),
            'amount' => rand(1, 5)
        ]);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_agent_user_confirm_deposit(): void
    {
        $response = $this->postJson('api/deposits/agent/' . $this->deposit->id . '/confirm');
        $response->assertStatus(200);

        $response = $this->postJson('api/deposits/agent/' . $this->deposit->id . '/confirm');
        $response->assertStatus(400);
    }

    public function test_agent_user_view_deposit(): void
    {
        $response = $this->getJson('api/deposits/agent');

        $response->assertStatus(200);
    }

    public function test_agent_user_cancel_deposit(): void
    {
        $response = $this->postJson('api/deposits/agent/' . $this->deposit->id . '/cancel');
        $response->assertStatus(200);
        $response = $this->postJson('api/deposits/agent/' . $this->deposit->id . '/cancel');
        $response->assertStatus(400);
    }
}
