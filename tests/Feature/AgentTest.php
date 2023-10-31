<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Enums\AgentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use App\Models\Agent;
use Tests\TestCase;

class AgentTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    private $agent;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->agent = Agent::query()->first();
        $this->withHeaders([
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
            'Authorization' =>  'Bearer ' . $this->getToken()
        ]);
    }

    public function test_admin_can_create_agent(): void
    {
        $name = $this->faker()->lastName();
        $response = $this->postJson('api/agents', [
            'name' => $name
        ]);
        $response->assertStatus(200);
        $this->assertNotNull(Agent::where('name', $name)->first());
    }

    public function test_newly_created_agent_status_is_default_to_normal(): void
    {
        $name = $this->faker()->lastName();
        $response = $this->postJson('api/agents', [
            'name' => $name
        ]);

        $this->assertEquals(
            AgentStatus::NORMAL->value,
            Agent::find($response->json()['agent']['id'])->status
        );
    }

    public function test_admin_can_view_agents(): void
    {
        $response = $this->getJson('api/agents');
        $response->assertStatus(200);
    }

    public function test_admin_can_restrict_agent(): void
    {
        $response = $this->postJson('api/agents/' . $this->agent->id . '/toggle-status')->json();
        $this->assertEquals(
            AgentStatus::RESTRICTED->value,
            $response['agent']['status']
        );
    }

    public function test_admin_can_change_agent_status_from_restrict_to_normal(): void
    {
        $response = $this->postJson('api/agents/' . $this->agent->id . '/toggle-status')->json();
        $this->assertEquals(
            AgentStatus::RESTRICTED->value,
            $response['agent']['status']
        );
        $response = $this->postJson('api/agents/' . $this->agent->id . '/toggle-status')->json();
        $this->assertEquals(
            AgentStatus::NORMAL->value,
            $response['agent']['status']
        );
    }

    public function test_admin_can_reset_agent_key(): void
    {
        $response = $this->postJson('api/agents/' . $this->agent->id . '/reset-key')->json();
        $this->assertNotEquals(
            $this->agent->key,
            $response['key']
        );
    }

    public function test_admin_can_update_agent_data(): void
    {
        $updateData = [
            'ip' => $this->faker()->ipv4(),
            'name' => $this->faker()->lastName(),
            'remark' => Str::random(20),
        ];
        $response = $this->putJson('api/agents/' . $this->agent->id, $updateData);
        $response->assertStatus(200);
        $this->assertEquals(1, Agent::where(['id' => $this->agent->id])->where($updateData)->count());
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
