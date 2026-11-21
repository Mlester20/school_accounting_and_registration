<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Enums\Sex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        \Spatie\Permission\Models\Role::create(['name' => 'admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => 'registrar', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => 'accounting', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => 'faculty', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => 'student', 'guard_name' => 'web']);
    }

    /** @test */
    public function login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    /** @test */
    public function login_with_user_id_succeeds(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $user->assignRole('admin');

        $response = $this->post('/login', [
            'user_id' => 'EMP-0001',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    /** @test */
    public function wrong_password_fails_with_generic_error(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $user->assignRole('admin');

        $response = $this->post('/login', [
            'user_id' => 'EMP-0001',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('user_id');
        $errors = session('errors');
        $this->assertEquals(trans('auth.failed'), $errors->first('user_id'));
    }

    /** @test */
    public function unknown_user_id_fails_with_same_generic_error(): void
    {
        $response = $this->post('/login', [
            'user_id' => 'UNKNOWN-0001',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('user_id');
        $errors = session('errors');
        $this->assertEquals(trans('auth.failed'), $errors->first('user_id'));
    }

    /** @test */
    public function email_cannot_be_used_to_log_in(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $user->assignRole('admin');

        $response = $this->post('/login', [
            'user_id' => 'admin@test.edu', // Using email as user_id
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('user_id');
    }

    /** @test */
    public function inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'inactive',
            'must_change_password' => false,
        ]);
        $user->assignRole('admin');

        $response = $this->post('/login', [
            'user_id' => 'EMP-0001',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('user_id');
        $errors = session('errors');
        $this->assertEquals(trans('auth.failed'), $errors->first('user_id'));
    }

    /** @test */
    public function locked_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'locked',
            'must_change_password' => false,
        ]);
        $user->assignRole('admin');

        $response = $this->post('/login', [
            'user_id' => 'EMP-0001',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('user_id');
        $errors = session('errors');
        $this->assertEquals(trans('auth.failed'), $errors->first('user_id'));
    }

    /** @test */
    public function lockout_after_5_failed_attempts(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $user->assignRole('admin');

        // Make 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'user_id' => 'EMP-0001',
                'password' => 'wrong-password',
            ]);
        }

        // 6th attempt should be throttled
        $response = $this->post('/login', [
            'user_id' => 'EMP-0001',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('user_id');
        $errors = session('errors');
        $this->assertStringContainsString('Too many login attempts. Please try again in', $errors->first('user_id'));
    }

    /** @test */
    public function last_login_at_is_updated_on_successful_login(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => false,
            'last_login_at' => null,
        ]);
        $user->assignRole('admin');

        $this->post('/login', [
            'user_id' => 'EMP-0001',
            'password' => 'password',
        ]);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertTrue($user->last_login_at->isAfter(now()->subMinute()));
    }

    /** @test */
    public function register_route_returns_404(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(404);

        $response = $this->post('/register', []);
        $response->assertStatus(404);
    }

    /** @test */
    public function forgot_password_route_returns_404(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertStatus(404);

        $response = $this->post('/forgot-password', []);
        $response->assertStatus(404);
    }

    /** @test */
    public function reset_password_route_returns_404(): void
    {
        $response = $this->get('/reset-password/token');
        $response->assertStatus(404);

        $response = $this->post('/reset-password', []);
        $response->assertStatus(404);
    }

    /** @test */
    public function verify_email_route_returns_404(): void
    {
        $response = $this->get('/verify-email');
        $response->assertStatus(404);

        $response = $this->get('/verify-email/1/hash');
        $response->assertStatus(404);

        $response = $this->post('/email/verification-notification', []);
        $response->assertStatus(404);
    }

    /** @test */
    public function throttle_key_is_lower_user_id_and_ip(): void
    {
        // Clear any existing rate limiter state
        RateLimiter::clear('emp-0001|127.0.0.1');

        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $user->assignRole('admin');

        // Make failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'user_id' => 'EMP-0001',
                'password' => 'wrong-password',
            ]);
        }

        // Should be locked out now
        $this->assertTrue(RateLimiter::tooManyAttempts('emp-0001|127.0.0.1', 5));
    }
}