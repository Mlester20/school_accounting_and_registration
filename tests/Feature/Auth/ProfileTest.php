<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Employee;
use App\Enums\Sex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::create(['name' => 'admin', 'guard_name' => 'web']);
    }

    /** @test */
    public function profile_page_shows_users_own_data(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $user->assignRole('admin');

        Employee::create([
            'user_fk_id' => $user->id,
            'employee_id' => 'EMP-0001',
            'first_name' => 'Test',
            'last_name' => 'Admin',
            'sex' => Sex::Male,
            'contact_number' => '09171234567',
            'position' => 'Admin',
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Profile/Edit')
            ->has('auth.user', fn ($user) => $user
                ->where('user_id', 'EMP-0001')
                ->where('full_name', 'Test Admin')
                ->where('roles', ['admin'])
                ->has('permissions')
                ->has('must_change_password')
            )
        );
    }

    /** @test */
    public function profile_page_can_update_password(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $user->assignRole('admin');

        Employee::create([
            'user_fk_id' => $user->id,
            'employee_id' => 'EMP-0001',
            'first_name' => 'Test',
            'last_name' => 'Admin',
            'sex' => Sex::Male,
            'contact_number' => '09171234567',
            'position' => 'Admin',
        ]);

        $response = $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'password',
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $user->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPass123', $user->password));
    }

    /** @test */
    public function delete_profile_route_returns_404(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->delete('/profile');

        $response->assertStatus(405);
    }

    /** @test */
    public function update_profile_route_returns_404(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'New Name',
            'email' => 'new@test.edu',
        ]);

        $response->assertStatus(405);
    }

    /** @test */
    public function users_cannot_delete_their_own_account(): void
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->delete('/profile');

        $response->assertStatus(405);
        $this->assertDatabaseHas('users', ['user_id' => 'EMP-0001']);
    }
}