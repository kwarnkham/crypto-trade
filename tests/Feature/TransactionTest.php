<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Transaction;
use App\Models\Admin;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    private $admin;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAs(Admin::first());
    }

    public function test_admin_can_list_transactions(): void
    {
        Transaction::factory()->count(5)->create();
        $response = $this->getJson('api/transactions');
        $response->assertOk();
        $this->assertArrayHasKey('data', $response->json());
        $this->assertNotEmpty($response->json()['data']);
    }
}
