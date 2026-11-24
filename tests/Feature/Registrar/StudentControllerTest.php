<?php

namespace Tests\Feature\Registrar;

use App\Enums\Sex;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class StudentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $registrar;
    protected User $accounting;
    protected User $faculty;
    protected User $studentUser;
    protected Student $student1; // CS program, has account
    protected Student $student2; // CS program, no account
    protected Student $student3; // Business program, no account
    protected Department $deptCs;
    protected Department $deptBusiness;
    protected Program $programBsit;
    protected Program $programBsa;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles and permissions
        $this->createRolesAndPermissions();

        // Create departments
        $this->deptCs = Department::create(['code' => 'CS', 'name' => 'College of Computer Studies']);
        $this->deptBusiness = Department::create(['code' => 'BUS', 'name' => 'College of Business Administration']);

        // Create programs
        $this->programBsit = Program::create([
            'department_id' => $this->deptCs->id,
            'code' => 'BSIT',
            'name' => 'Bachelor of Science in Information Technology',
            'years_to_complete' => 4,
        ]);

        $this->programBsa = Program::create([
            'department_id' => $this->deptBusiness->id,
            'code' => 'BSA',
            'name' => 'Bachelor of Science in Accountancy',
            'years_to_complete' => 4,
        ]);

        // Create admin
        $this->admin = User::create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $this->admin->assignRole('admin');

        // Create registrar (assigned to CS department only)
        $this->registrar = User::create([
            'user_id' => 'EMP-0002',
            'email' => 'registrar@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $this->registrar->assignRole('registrar');

        $registrarEmp = Employee::create([
            'user_fk_id' => $this->registrar->id,
            'employee_id' => 'EMP-0002',
            'first_name' => 'Test',
            'last_name' => 'Registrar',
            'sex' => Sex::Female,
            'contact_number' => '09171234567',
            'position' => 'Registrar',
        ]);
        $registrarEmp->departments()->attach($this->deptCs->id);

        // Create accounting
        $this->accounting = User::create([
            'user_id' => 'EMP-0003',
            'email' => 'accounting@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $this->accounting->assignRole('accounting');

        $accountingEmp = Employee::create([
            'user_fk_id' => $this->accounting->id,
            'employee_id' => 'EMP-0003',
            'first_name' => 'Test',
            'last_name' => 'Accounting',
            'sex' => Sex::Male,
            'contact_number' => '09181234567',
            'position' => 'Accounting Officer',
        ]);
        $accountingEmp->departments()->attach($this->deptBusiness->id);

        // Create faculty
        $this->faculty = User::create([
            'user_id' => 'EMP-0004',
            'email' => 'faculty@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $this->faculty->assignRole('faculty');

        $facultyEmp = Employee::create([
            'user_fk_id' => $this->faculty->id,
            'employee_id' => 'EMP-0004',
            'first_name' => 'Test',
            'last_name' => 'Faculty',
            'sex' => Sex::Female,
            'contact_number' => '09191234567',
            'position' => 'Instructor',
        ]);
        $facultyEmp->departments()->attach($this->deptCs->id);

        // Create student user (has account)
        $this->studentUser = User::create([
            'user_id' => '2025-00001',
            'email' => 'student1@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $this->studentUser->assignRole('student');

        // Create students
        $this->student1 = Student::create([
            'user_fk_id' => $this->studentUser->id,
            'student_id' => '2025-00001',
            'first_name' => 'Student',
            'last_name' => 'One',
            'sex' => Sex::Male,
            'program_id' => $this->programBsit->id,
            'year_level' => 1,
            'status' => 'active',
        ]);

        $this->student2 = Student::create([
            'user_fk_id' => null,
            'student_id' => '2025-00002',
            'first_name' => 'Student',
            'last_name' => 'Two',
            'sex' => Sex::Female,
            'program_id' => $this->programBsit->id,
            'year_level' => 1,
            'status' => 'active',
        ]);

        $this->student3 = Student::create([
            'user_fk_id' => null,
            'student_id' => '2025-00003',
            'first_name' => 'Student',
            'last_name' => 'Three',
            'sex' => Sex::Male,
            'program_id' => $this->programBsa->id,
            'year_level' => 1,
            'status' => 'active',
        ]);
    }

    private function createRolesAndPermissions(): void
    {
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'registrar', 'guard_name' => 'web']);
        Role::create(['name' => 'accounting', 'guard_name' => 'web']);
        Role::create(['name' => 'faculty', 'guard_name' => 'web']);
        Role::create(['name' => 'student', 'guard_name' => 'web']);

        $permissions = [
            'students.view',
            'students.manage',
            'students.import',
            'accounts.student.create',
            'accounts.student.manage',
            'accounts.faculty.create',
            'accounts.staff.create',
            'enrollment.manage',
            'academic.manage',
            'audit.view',
        ];

        foreach ($permissions as $perm) {
            Permission::create(['name' => $perm, 'guard_name' => 'web']);
        }

        $registrarRole = Role::findByName('registrar');
        $registrarRole->givePermissionTo([
            'students.view',
            'students.manage',
            'accounts.student.create',
            'accounts.student.manage',
        ]);

        $adminRole = Role::findByName('admin');
        $adminRole->givePermissionTo($permissions);
    }

    /** @test */
    public function registrar_sees_only_department_students_in_index()
    {
        $this->actingAs($this->registrar);

        $response = $this->get(route('registrar.students.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Registrar/Students/Index')
            ->has('students', 2) // student1 and student2
            ->where('students.0.student_id', '2025-00001')
            ->where('students.1.student_id', '2025-00002')
            ->whereNot('students', '2025-00003')
        );
    }

    /** @test */
    public function registrar_search_excludes_out_of_department()
    {
        $this->actingAs($this->registrar);

        // Search for student3 (Business program) by name
        $response = $this->get(route('registrar.students.index', ['search' => 'Student Three']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Registrar/Students/Index')
            ->has('students', 0)
        );
    }

    /** @test */
    public function registrar_filters_work()
    {
        $this->actingAs($this->registrar);

        // Filter by program
        $response = $this->get(route('registrar.students.index', ['program_id' => $this->programBsit->id]));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Registrar/Students/Index')
            ->has('students', 2)
        );

        // Filter by account status: has_account
        $response = $this->get(route('registrar.students.index', ['account_status' => 'has_account']));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Registrar/Students/Index')
            ->has('students', 1)
            ->where('students.0.student_id', '2025-00001')
        );

        // Filter by account status: no_account
        $response = $this->get(route('registrar.students.index', ['account_status' => 'no_account']));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Registrar/Students/Index')
            ->has('students', 1)
            ->where('students.0.student_id', '2025-00002')
        );
    }

    /** @test */
    public function registrar_cannot_access_out_of_department_student_show()
    {
        $this->actingAs($this->registrar);

        $response = $this->get(route('registrar.students.show', $this->student3->id));

        $response->assertNotFound();
    }

    /** @test */
    public function registrar_cannot_access_out_of_department_student_edit()
    {
        $this->actingAs($this->registrar);

        $response = $this->get(route('registrar.students.edit', $this->student3->id));

        $response->assertNotFound();
    }

    /** @test */
    public function registrar_cannot_update_out_of_department_student()
    {
        $this->actingAs($this->registrar);

        $response = $this->put(route('registrar.students.update', $this->student3->id), [
            'first_name' => 'Hacked',
            'last_name' => 'Student',
            'sex' => 'male',
            'year_level' => 1,
            'program_id' => $this->programBsit->id,
        ]);

        $response->assertNotFound();
    }

    /** @test */
    public function registrar_cannot_access_out_of_department_account_routes()
    {
        $this->actingAs($this->registrar);

        // Create account - 404 for out of scope student
        $response = $this->post(route('registrar.students.account.store', $this->student3->id));
        $response->assertNotFound();

        // Bulk create accounts - returns 302 with failed results (student not in scope)
        $response = $this->post(route('registrar.students.accounts.bulk'), ['student_ids' => [$this->student3->id]]);
        $response->assertRedirect(route('registrar.students.index'));
        $this->assertNotNull(session('bulk_failed'));
        $failed = session('bulk_failed');
        $this->assertCount(1, $failed);
        $this->assertEquals($this->student3->id, $failed[0]['student_id']);

        // Reset password - 404 for out of scope student
        $response = $this->post(route('registrar.students.account.reset-password', $this->student3->id));
        $response->assertNotFound();

        // Update status - 404 for out of scope student
        $response = $this->put(route('registrar.students.account.status', $this->student3->id), ['status' => 'inactive']);
        $response->assertNotFound();
    }

    /** @test */
    public function create_student_with_out_of_department_program_fails_validation()
    {
        $this->actingAs($this->registrar);

        $response = $this->post(route('registrar.students.store'), [
            'student_id' => '2025-00004',
            'first_name' => 'New',
            'last_name' => 'Student',
            'sex' => 'male',
            'contact_number' => '09201234567',
            'year_level' => 1,
            'program_id' => $this->programBsa->id, // Business program - not in registrar's departments
        ]);

        $response->assertSessionHasErrors('program_id');
    }

    /** @test */
    public function create_student_with_correct_program_succeeds()
    {
        $this->actingAs($this->registrar);

        $response = $this->post(route('registrar.students.store'), [
            'student_id' => '2025-00004',
            'first_name' => 'New',
            'last_name' => 'Student',
            'sex' => 'male',
            'contact_number' => '09201234567',
            'year_level' => 1,
            'program_id' => $this->programBsit->id, // CS program - in registrar's departments
        ]);

        $response->assertRedirect(route('registrar.students.index'));
        $this->assertDatabaseHas('students', [
            'student_id' => '2025-00004',
            'first_name' => 'New',
            'last_name' => 'Student',
            'program_id' => $this->programBsit->id,
        ]);
    }

    /** @test */
    public function create_student_invalid_student_id_format_fails()
    {
        $this->actingAs($this->registrar);

        $response = $this->post(route('registrar.students.store'), [
            'student_id' => 'invalid-format',
            'first_name' => 'New',
            'last_name' => 'Student',
            'sex' => 'male',
            'year_level' => 1,
            'program_id' => $this->programBsit->id,
        ]);

        $response->assertSessionHasErrors('student_id');
    }

    /** @test */
    public function create_student_duplicate_student_id_fails()
    {
        $this->actingAs($this->registrar);

        $response = $this->post(route('registrar.students.store'), [
            'student_id' => '2025-00001', // Already exists (student1)
            'first_name' => 'New',
            'last_name' => 'Student',
            'sex' => 'male',
            'year_level' => 1,
            'program_id' => $this->programBsit->id,
        ]);

        $response->assertSessionHasErrors('student_id');
    }

    /** @test */
    public function update_student_student_id_unchanged()
    {
        $this->actingAs($this->registrar);

        $originalStudentId = $this->student2->student_id;

        $response = $this->put(route('registrar.students.update', $this->student2->id), [
            'student_id' => '2025-99999', // Should be ignored
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'sex' => 'female',
            'year_level' => 2,
            'program_id' => $this->programBsit->id,
        ]);

        $response->assertRedirect(route('registrar.students.index'));
        $this->student2->refresh();
        $this->assertEquals($originalStudentId, $this->student2->student_id);
        $this->assertEquals('Updated', $this->student2->first_name);
        $this->assertEquals('Name', $this->student2->last_name);
    }

    /** @test */
    public function update_student_ignores_user_fk_id_and_status()
    {
        $this->actingAs($this->registrar);

        $response = $this->put(route('registrar.students.update', $this->student2->id), [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'sex' => 'female',
            'year_level' => 2,
            'program_id' => $this->programBsit->id,
            'user_fk_id' => 999, // Should be ignored
            'status' => 'inactive', // Should be ignored
        ]);

        $response->assertRedirect(route('registrar.students.index'));
        $this->student2->refresh();
        $this->assertNull($this->student2->user_fk_id);
        $this->assertEquals('active', $this->student2->status);
    }

    /** @test */
    public function student_role_gets_403_on_registrar_routes()
    {
        $this->actingAs($this->studentUser);

        $response = $this->get(route('registrar.students.index'));
        $response->assertForbidden();

        $response = $this->get(route('registrar.students.show', $this->student1->id));
        $response->assertForbidden();
    }

    /** @test */
    public function admin_accounting_faculty_student_get_403_on_registrar_students_routes()
    {
        $routes = [
            'registrar.students.index',
            'registrar.students.create',
            'registrar.students.store',
            'registrar.students.show',
            'registrar.students.edit',
            'registrar.students.update',
            'registrar.students.account.store',
            'registrar.students.accounts.bulk',
            'registrar.students.account.reset-password',
            'registrar.students.account.status',
        ];

        foreach ([$this->admin, $this->accounting, $this->faculty, $this->studentUser] as $user) {
            $this->actingAs($user);

            foreach ($routes as $routeName) {
                $route = route($routeName, ['student' => $this->student1->id]);
                $response = $this->get($route);
                if ($routeName === 'registrar.students.index') {
                    $response->assertForbidden();
                }
            }
        }
    }

    /** @test */
    public function guest_redirects_to_login()
    {
        $response = $this->get(route('registrar.students.index'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function credentials_flash_key_absent_on_next_request_to_same_page()
    {
        $this->actingAs($this->registrar);

        // Simulate the account-creation flow: credentials land in the session via
        // redirect()->with('credentials', ...) (what the Actions' controllers do).
        $this->withSession(['credentials' => [
            ['student_id' => '2025-00002', 'user_id' => '2025-00002', 'temporary_password' => 'temp-pass-123'],
        ]]);

        // First index request after creation: credentials arrive via the Inertia
        // flash bag (top-level page.flash, not props, so never in history state).
        $response = $this->get(route('registrar.students.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Registrar/Students/Index')
            ->hasFlash('credentials')
        );
        // The session copy is consumed exactly once by pull().
        $this->assertNull(session('credentials'));

        // Second request to the same page: credentials must be gone entirely —
        // neither as a prop nor in the flash bag.
        $response = $this->get(route('registrar.students.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Registrar/Students/Index')
            ->missingFlash('credentials')
            ->missing('flash.credentials')
        );
        $this->assertNull(session('credentials'));
    }
}