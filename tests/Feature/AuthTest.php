<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;
    private $adminPassword;
    private $admin;

    public function setUp(): void
    {
        parent::setUp();
        $this->adminPassword = Str::random(6);
        $this->admin = Admin::factory(['password' => bcrypt($this->adminPassword)])->create();
    }

    public function test_admin_login(): void
    {
        $response = $this->postJson('api/admin/login', [
            'name' => $this->admin->name,
            'password' => $this->adminPassword
        ]);

        $response->assertStatus(200);
    }

    public function test_admin_can_change_password(): void
    {
        $updatedPassword = Str::random(6);
        $response = $this->actingAs($this->admin)->postJson('api/admin/change-password', [
            'password' => $this->adminPassword,
            'new_password' => $updatedPassword,
            'new_password_confirmation' => $updatedPassword,
        ]);


        $response->assertStatus(200);

        $this->postJson('api/admin/login', [
            'name' => $this->admin->name,
            'password' => $this->adminPassword
        ])->assertStatus(401);

        $this->postJson('api/admin/login', [
            'name' => $this->admin->name,
            'password' => $updatedPassword
        ])->assertOk();
    }
}
