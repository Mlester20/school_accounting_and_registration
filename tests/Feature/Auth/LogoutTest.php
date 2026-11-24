<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::create(['name' => 'admin', 'guard_name' => 'web']);
    }

    /** @test */
    public function logout_works(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    /** @test */
    public function user_with_must_change_password_can_log_out_and_reach_login(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0009',
            'email' => 'forced@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => true, // EnsurePasswordIsChanged would normally intercept
        ]);
        $user->assignRole('admin');

        // Logout passes through the middleware even with must_change_password
        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');

        // After logging out, the guest can reach the login page
        $response = $this->followingRedirects()->get('/login');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Login')
        );
    }
}