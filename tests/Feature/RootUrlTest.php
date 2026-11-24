<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RootUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'registrar', 'accounting', 'faculty', 'student'] as $role) {
            \Spatie\Permission\Models\Role::create(['name' => $role, 'guard_name' => 'web']);
        }
    }

    protected function createUserWithRole(string $userId, string $role): User
    {
        $user = User::factory()->create([
            'user_id' => $userId,
            'email' => "{$userId}@test.edu",
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $user->assignRole($role);
        return $user;
    }

    /** @test */
    public function guest_visiting_root_url_is_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function authenticated_registrar_visiting_root_url_is_redirected_to_dashboard(): void
    {
        $user = $this->createUserWithRole('EMP-0002', 'registrar');

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(302);
        $response->assertRedirect(route('dashboard'));
    }

    /** @test */
    public function registrar_hitting_dashboard_is_redirected_to_registrar_area(): void
    {
        $user = $this->createUserWithRole('EMP-0002', 'registrar');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(302);
        $response->assertRedirect(route('registrar.dashboard'));
    }

    /** @test */
    public function login_page_renders_auth_login_component_for_guests(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Login')
        );
    }

    /** @test */
    public function authenticated_user_hitting_login_is_redirected(): void
    {
        $user = $this->createUserWithRole('EMP-0002', 'registrar');

        $response = $this->actingAs($user)->get('/login');

        $response->assertStatus(302);
        $response->assertRedirect(route('dashboard'));
    }

    /** @test */
    public function removed_auth_routes_still_return_404(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password', [])->assertNotFound();
        $this->get('/reset-password/some-token')->assertNotFound();
        $this->post('/reset-password', [])->assertNotFound();
        $this->get('/verify-email')->assertNotFound();
        $this->get('/verify-email/1/hash')->assertNotFound();
        $this->post('/email/verification-notification', [])->assertNotFound();
    }
}
