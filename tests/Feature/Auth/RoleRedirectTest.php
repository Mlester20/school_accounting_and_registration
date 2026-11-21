<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Enums\Sex;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleRedirectTest extends TestCase
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

    protected function createEmployeeForUser(User $user, string $employeeId, Department $dept = null): Employee
    {
        $emp = Employee::create([
            'user_fk_id' => $user->id,
            'employee_id' => $employeeId,
            'first_name' => 'Test',
            'last_name' => 'User',
            'sex' => Sex::Male,
            'contact_number' => '09171234567',
            'position' => 'Test',
        ]);
        if ($dept) {
            $emp->departments()->attach($dept->id);
        }
        return $emp;
    }

    /** @test */
    public function admin_redirects_to_admin_dashboard(): void
    {
        $user = $this->createUserWithRole('EMP-0001', 'admin');
        $this->createEmployeeForUser($user, 'EMP-0001');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('admin.dashboard'));
    }

    /** @test */
    public function registrar_redirects_to_registrar_dashboard(): void
    {
        $dept = Department::create(['code' => 'CS', 'name' => 'College of Computer Studies']);
        $user = $this->createUserWithRole('EMP-0002', 'registrar');
        $this->createEmployeeForUser($user, 'EMP-0001', $dept);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('registrar.dashboard'));
    }

    /** @test */
    public function accounting_redirects_to_accounting_dashboard(): void
    {
        $user = $this->createUserWithRole('EMP-0003', 'accounting');
        $this->createEmployeeForUser($user, 'EMP-0002');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('accounting.dashboard'));
    }

    /** @test */
    public function faculty_redirects_to_faculty_dashboard(): void
    {
        $dept = Department::create(['code' => 'CS', 'name' => 'College of Computer Studies']);
        $user = $this->createUserWithRole('EMP-0004', 'faculty');
        $this->createEmployeeForUser($user, 'EMP-0003', $dept);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('faculty.dashboard'));
    }

    /** @test */
    public function student_redirects_to_student_dashboard(): void
    {
        $user = $this->createUserWithRole('2025-00001', 'student');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('student.dashboard'));
    }

    /** @test */
    public function role_priority_admin_over_registrar(): void
    {
        $user = $this->createUserWithRole('EMP-0001', 'admin');
        $user->assignRole('registrar'); // Also has registrar role
        $this->createEmployeeForUser($user, 'EMP-0001');

        $response = $this->actingAs($user)->get('/dashboard');

        // Admin has higher priority
        $response->assertRedirect(route('admin.dashboard'));
    }

    /** @test */
    public function role_priority_registrar_over_accounting(): void
    {
        $user = $this->createUserWithRole('EMP-0002', 'registrar');
        $user->assignRole('accounting'); // Also has accounting role
        $this->createEmployeeForUser($user, 'EMP-0001');

        $response = $this->actingAs($user)->get('/dashboard');

        // Registrar has higher priority than accounting
        $response->assertRedirect(route('registrar.dashboard'));
    }

    /** @test */
    public function role_priority_accounting_over_faculty(): void
    {
        $user = $this->createUserWithRole('EMP-0003', 'accounting');
        $user->assignRole('faculty');
        $this->createEmployeeForUser($user, 'EMP-0002');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('accounting.dashboard'));
    }

    /** @test */
    public function role_priority_faculty_over_student(): void
    {
        $user = $this->createUserWithRole('EMP-0004', 'faculty');
        $user->assignRole('student');
        $this->createEmployeeForUser($user, 'EMP-0003');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('faculty.dashboard'));
    }

    /** @test */
    public function guest_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function admin_can_access_admin_area(): void
    {
        $user = $this->createUserWithRole('EMP-0001', 'admin');
        $this->createEmployeeForUser($user, 'EMP-0001');

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_cannot_access_registrar_area(): void
    {
        $user = $this->createUserWithRole('EMP-0001', 'admin');
        $this->createEmployeeForUser($user, 'EMP-0001');

        $response = $this->actingAs($user)->get('/registrar');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_cannot_access_accounting_area(): void
    {
        $user = $this->createUserWithRole('EMP-0001', 'admin');
        $this->createEmployeeForUser($user, 'EMP-0001');

        $response = $this->actingAs($user)->get('/accounting');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_cannot_access_faculty_area(): void
    {
        $user = $this->createUserWithRole('EMP-0001', 'admin');
        $this->createEmployeeForUser($user, 'EMP-0001');

        $response = $this->actingAs($user)->get('/faculty');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_cannot_access_student_area(): void
    {
        $user = $this->createUserWithRole('EMP-0001', 'admin');
        $this->createEmployeeForUser($user, 'EMP-0001');

        $response = $this->actingAs($user)->get('/student');

        $response->assertStatus(403);
    }

    /** @test */
    public function registrar_cannot_access_admin_area(): void
    {
        $dept = Department::create(['code' => 'CS', 'name' => 'College of Computer Studies']);
        $user = $this->createUserWithRole('EMP-0002', 'registrar');
        $this->createEmployeeForUser($user, 'EMP-0001', $dept);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(403);
    }

    /** @test */
    public function accounting_cannot_access_admin_area(): void
    {
        $user = $this->createUserWithRole('EMP-0003', 'accounting');
        $this->createEmployeeForUser($user, 'EMP-0002');

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(403);
    }

    /** @test */
    public function user_with_no_role_gets_403_on_dashboard(): void
    {
        $user = User::factory()->create([
            'user_id' => 'NOROLE-0001',
            'email' => 'norole@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        // No role assigned
        $this->createEmployeeForUser($user, 'EMP-0001');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(403);
    }
}