<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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
    private $recipientUser;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->agent = Agent::query()->first();
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
        $this->withHeaders([
            'x-agent'   => $this->agent->name,
            'x-api-key' => $this->agent->jwtKey(),
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
            'Authorization' =>  'Bearer ' . $this->getToken()
        ]);
    }

    public function test_agent_user_can_transfer_USDT_to_each_other(): void
    {
        $response = $this->postJson('api/transfers/agent', [
            'from' => $this->user->code,
            'to' => $this->recipientUser->code,
            'amount' => rand(2, 5)
        ]);
        $response->assertStatus(200);
        $this->assertNotNull(Transfer::where('id', $response->json()['transfer']['id'])->first());
    }

    public function test_agent_user_cant_transfer_over_balance_amount(): void
    {
        $response = $this->postJson('api/transfers/agent', [
            'from' => $this->user->code,
            'to' => $this->recipientUser->code,
            'amount' => rand(6, 10)
        ]);
        $response->assertStatus(400);
        $this->assertNull(Transfer::first());
    }

    public function test_charges_is_created_altogether_with_transfer_creation(): void
    {
        $response = $this->postJson('api/transfers/agent', [
            'from' => $this->user->code,
            'to' => $this->recipientUser->code,
            'amount' => rand(2, 5)
        ]);
        $response->assertStatus(200);

        $this->assertNotNull(Charge::where('chargeable_id', $response->json()['transfer']['id']));
    }

    public function test_balance_amount_is_deduced_from_transfered_user_account(): void
    {
        $amount = rand(2, 5);
        $response = $this->postJson('api/transfers/agent', [
            'from' => $this->user->code,
            'to' => $this->recipientUser->code,
            'amount' => $amount
        ]);
        $response->assertStatus(200);
        $transferUserBalance = User::find($response->json()['transfer']['user_id'])->balance;
        $this->assertEquals(($this->user->balance - $amount), $transferUserBalance);
    }

    public function test_balance_amount_increase_to_recipient_user_account(): void
    {
        $amount = rand(2, 5);
        $response = $this->postJson('api/transfers/agent', [
            'from' => $this->user->code,
            'to' => $this->recipientUser->code,
            'amount' => $amount
        ]);
        $response->assertStatus(200);
        $recipientUserBalance = User::find($response->json()['transfer']['recipient_id'])->balance;
        $this->assertEquals(($this->recipientUser->balance + $amount - $response->json()['transfer']['fee']), $recipientUserBalance);
    }

    public function test_agent_user_can_view_transfer(): void
    {
        $transferCreate = Transfer::factory(['user_id' => $this->user->id])->count(5)->create();
        $response = $this->getJson('api/transfers/agent');
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json()['data']);
    }

    public function test_admin_can_view_transfer(): void
    {
        $transferCreate = Transfer::factory(['user_id' => $this->user->id])->count(5)->create();
        $response = $this->getJson('api/transfers');
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json()['data']);
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
