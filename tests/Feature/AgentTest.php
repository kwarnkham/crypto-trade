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
        $this->agent = Agent::first();
        $this->actingAs(Admin::first());
    }

    public function test_admin_can_create_agent(): void
    {
        $name = $this->faker()->lastName();
        $response = $this->postJson('api/agents', [
            'name' => $name
        ]);
        $response->assertOk();
        $this->assertNotNull(Agent::where('name', $name)->first());
        $this->assertArrayHasKey('agent', $response->json());
        $this->assertArrayHasKey('key', $response->json());
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

    public function test_admin_can_list_agents(): void
    {
        Agent::factory()->count(5)->create();
        $response = $this->getJson('api/agents');
        $response->assertStatus(200);
        $this->assertArrayHasKey('data', $response->json());
        $this->assertNotNull($response->json()['data']);
    }

    public function test_admin_can_toggle_agent_status(): void
    {
        $response = $this->postJson('api/agents/' . $this->agent->id . '/toggle-status');
        $this->assertEquals(
            AgentStatus::RESTRICTED->value,
            $response->json()['agent']['status']
        );

        $response = $this->postJson('api/agents/' . $this->agent->id . '/toggle-status');
        $this->assertEquals(
            AgentStatus::NORMAL->value,
            $response->json()['agent']['status']
        );
    }

    public function test_admin_can_change_agent_status_from_restrict_to_normal(): void
    {
        $response = $this->postJson('api/agents/' . $this->agent->id . '/toggle-status');
        $this->assertEquals(
            AgentStatus::RESTRICTED->value,
            $response->json()['agent']['status']
        );
        $response = $this->postJson('api/agents/' . $this->agent->id . '/toggle-status');
        $this->assertEquals(
            AgentStatus::NORMAL->value,
            $response->json()['agent']['status']
        );
    }

    public function test_admin_can_reset_agent_key(): void
    {
        $oldKey = $this->agent->key;
        $response = $this->postJson('api/agents/' . $this->agent->id . '/reset-key');

        $this->assertNotEquals(
            $oldKey,
            $response->json()['key']
        );


        $this->assertEquals(
            $this->agent->fresh()->key,
            $response->json()['key']
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
        $this->assertEquals(1, Agent::where(['id' => $this->agent->id])
            ->where($updateData)->count());
        $this->assertArrayHasKey('agent', $response->json());
    }
}
