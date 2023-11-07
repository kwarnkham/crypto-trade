<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Admin;
use App\Models\Agent;
use App\Models\User;
use App\Models\Transfer;
use App\Models\Charge;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    private $user;
    private $agent;
    private $admin;
    private $recipientUser;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->agent = Agent::first();
        $this->actingAs(Admin::first());
        $this->withHeaders([
            'x-agent'   => $this->agent->name,
            'x-api-key' => $this->agent->jwtKey(),
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json'
        ]);
        $this->user = User::create([
            'code' =>  Str::random('3'),
            'name' => $this->faker()->lastName(),
            'balance' => 5,
            'agent_id' =>   $this->agent->id,
        ]);
        $this->recipientUser = User::create([
            'code' =>  Str::random('3'),
            'name' => $this->faker()->lastName(),
            'balance' => 5,
            'agent_id' =>   $this->agent->id,
        ]);
    }

    public function test_agent_user_can_transfer_USDT_to_each_other(): void
    {
        $response = $this->postJson('api/transfers/agent', [
            'from' => $this->user->code,
            'to' => $this->recipientUser->code,
            'amount' => rand(2, 5)
        ]);
        $response->assertOK();
        $this->assertArrayHasKey('transfer', $response->json());
        $this->assertNotNull(Transfer::where('id', $response->json()['transfer']['id'])->first());
    }

    public function test_agent_user_cannot_transfer_over_balance_amount_to_other(): void
    {
        $response = $this->postJson('api/transfers/agent', [
            'from' => $this->user->code,
            'to' => $this->recipientUser->code,
            'amount' => rand(6, 10)
        ]);
        $response->assertBadRequest();
        $this->assertNull(Transfer::first());
    }

    public function test_transfered_charges_is_created_altogether_with_transfer_creation(): void
    {
        $response = $this->postJson('api/transfers/agent', [
            'from' => $this->user->code,
            'to' => $this->recipientUser->code,
            'amount' => rand(2, 5)
        ]);
        $response->assertOk();

        $this->assertNotNull(Charge::where('chargeable_id', $response->json()['transfer']['id']));
    }

    public function test_transfered_amount_is_deduced_from_the_balance_amount_of_transfered_user_account(): void
    {
        $transferAmount = rand(2, 5);
        $response = $this->postJson('api/transfers/agent', [
            'from' => $this->user->code,
            'to' => $this->recipientUser->code,
            'amount' => $transferAmount
        ]);
        $response->assertOk();
        $this->assertEquals(($this->user->balance - $transferAmount), $this->user->fresh()->balance);
    }

    public function test_transfered_amount_is_increased_to_the_balance_amount_of_recipient_user_account(): void
    {
        $transferAmount = rand(2, 5);
        $response = $this->postJson('api/transfers/agent', [
            'from' => $this->user->code,
            'to' => $this->recipientUser->code,
            'amount' => $transferAmount
        ]);
        $response->assertStatus(200);
        $transferFee = $response->json()['transfer']['fee'];
        $this->assertEquals(($this->recipientUser->balance + $transferAmount - $transferFee), $this->recipientUser->fresh()->balance);
    }

    public function test_agent_user_can_list_transfers(): void
    {
        Transfer::factory(['user_id' => $this->user->id])->count(5)->for(User::factory()->for(Agent::factory()->create())->create(), 'recipient')->create();
        $response = $this->getJson('api/transfers/agent')->assertOk();
        $this->assertArrayHasKey('data', $response->json());
        $this->assertNotEmpty($response->json()['data']);
    }

    public function test_admin_can_list_transfers(): void
    {
        Transfer::factory(['user_id' => $this->user->id])->count(5)->for(User::factory()->for(Agent::factory()->create())->create(), 'recipient')->create();
        $response = $this->getJson('api/transfers')->assertOk();
        $this->assertArrayHasKey('data', $response->json());
        $this->assertNotEmpty($response->json()['data']);
    }
}
