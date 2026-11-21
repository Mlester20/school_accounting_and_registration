<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForceChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::create(['name' => 'admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => 'registrar', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => 'accounting', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => 'faculty', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => 'student', 'guard_name' => 'web']);
    }

    /** @test */
    public function must_change_password_redirects_to_change_page(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => true,
        ]);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/admin');

        $response->assertRedirect(route('password.change'));
    }

    /** @test */
    public function force_change_password_page_can_be_rendered(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => true,
        ]);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/force-change-password');

        $response->assertStatus(200);
    }

    /** @test */
    public function password_can_be_changed_successfully(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => true,
        ]);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->post('/force-change-password', [
            'current_password' => 'password',
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPass123', $user->password));
    }

    /** @test */
    public function new_password_must_differ_from_current(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => true,
        ]);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->post('/force-change-password', [
            'current_password' => 'password',
            'password' => 'password', // Same as current
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('password');
        $user->refresh();
        $this->assertTrue($user->must_change_password);
    }

    /** @test */
    public function current_password_is_required(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => true,
        ]);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->post('/force-change-password', [
            'current_password' => '',
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    /** @test */
    public function password_must_meet_defaults(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => true,
        ]);
        $user->assignRole('admin');

        // Too short
        $response = $this->actingAs($user)->post('/force-change-password', [
            'current_password' => 'password',
            'password' => 'Pass1',
            'password_confirmation' => 'Pass1',
        ]);
        $response->assertSessionHasErrors('password');

        // No mixed case
        $response = $this->actingAs($user)->post('/force-change-password', [
            'current_password' => 'password',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertSessionHasErrors('password');

        // No numbers
        $response = $this->actingAs($user)->post('/force-change-password', [
            'current_password' => 'password',
            'password' => 'PasswordPass',
            'password_confirmation' => 'PasswordPass',
        ]);
        $response->assertSessionHasErrors('password');

        // Valid password
        $response = $this->actingAs($user)->post('/force-change-password', [
            'current_password' => 'password',
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ]);
        $response->assertRedirect(route('dashboard'));
    }

    /** @test */
    public function password_must_be_confirmed(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => true,
        ]);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->post('/force-change-password', [
            'current_password' => 'password',
            'password' => 'NewPass123',
            'password_confirmation' => 'DifferentPass123',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /** @test */
    public function after_changing_password_user_can_access_protected_pages(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => true,
        ]);
        $user->assignRole('admin');

        // Change password
        $this->actingAs($user)->post('/force-change-password', [
            'current_password' => 'password',
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ]);

        // Now should be able to access admin area
        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(200);
    }

    /** @test */
    public function wrong_current_password_fails(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => true,
        ]);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->post('/force-change-password', [
            'current_password' => 'wrong-password',
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ]);

        $response->assertSessionHasErrors('current_password');
        $user->refresh();
        $this->assertTrue($user->must_change_password);
    }
}