<?php

namespace Tests\Feature;

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
    use RefreshDatabase;
    private $agent;
    private $user;
    private $wallet;
    private $deposit;
    private $deposit_id;

    public function setUp(): void
    {
        parent::setUp();
        $this->wallet = Wallet::factory(['activated_at'=>now()])->create();
        $this->agent = Agent::factory(['key' => Str::random(64)])->create();
        $this->user = User::factory(['agent_id' =>$this->agent->id])->create();
        $this->deposit = Deposit::factory(['user_id' =>$this->user->id, 'wallet_id' =>$this->wallet->id])->create();
        $this->withHeaders([
            'x-agent'   => $this->agent->name,
            'x-api-key' => $this->agent->key,
            'Content-Type'  => 'application/x-www-form-urlencoded',
            // 'Authorization' => 'Bearer ' . $this->getBearerToken(),
            'Accept' => 'application/json'
        ]);
    }

    /**
     * Agent deposit
     */
    public function test_agent_user_create_deposit(): void
    {
        $delete_deposit = Deposit::whereNotNull('id')->delete();
        $response = $this->postJson('api/deposits/agent', [
            'code' => $this->user->code,
            'name' => $this->user->name,
            'amount' => rand(1,5)
        ]);
        $response->assertStatus(200);

        $response = $this->postJson('api/deposits/agent', [
            'code' => $this->user->code,
            'name' => $this->user->name,
            'amount' => rand(1,5)
        ]);
        $response->assertStatus(400);
    }

    public function test_agent_user_confirm_deposit(): void
    {
        $response = $this->postJson('api/deposits/agent/'.$this->deposit->id.'/confirm');
        $response->assertStatus(200);

        $response = $this->postJson('api/deposits/agent/'.$this->deposit->id.'/confirm');
        $response->assertStatus(400);
    }

    public function test_agent_user_view_deposit(): void
    {
        $response = $this->getJson('api/deposits/agent');

        $response->assertStatus(200);
    }

    public function test_agent_user_cancel_deposit(): void
    {
        $response = $this->postJson('api/deposits/agent/'.$this->deposit->id.'/cancel');
        $response->assertStatus(200);
        $response = $this->postJson('api/deposits/agent/'.$this->deposit->id.'/cancel');
        $response->assertStatus(400);
    }
}
