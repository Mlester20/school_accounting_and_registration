<?php

namespace Tests\Feature\Policies;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Enums\Sex;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class StudentPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $registrar;
    protected User $accounting;
    protected User $faculty;
    protected User $studentUser;
    protected Student $studentCs; // CS program (registrar's department), has account
    protected Student $studentBus; // Business program (NOT registrar's department), no account
    protected Student $studentCsNoAccount; // CS program (registrar's department), no account
    protected Department $deptCs;
    protected Department $deptBusiness;
    protected Program $programBsit;
    protected Program $programBsa;

    protected function setUp(): void
    {
        parent::setUp();

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
        $this->studentCs = Student::create([
            'user_fk_id' => $this->studentUser->id,
            'student_id' => '2025-00001',
            'first_name' => 'Student',
            'last_name' => 'CS',
            'sex' => Sex::Male,
            'program_id' => $this->programBsit->id,
            'year_level' => 1,
            'status' => 'active',
        ]);

        $this->studentBus = Student::create([
            'user_fk_id' => null,
            'student_id' => '2025-00002',
            'first_name' => 'Student',
            'last_name' => 'Business',
            'sex' => Sex::Female,
            'program_id' => $this->programBsa->id,
            'year_level' => 1,
            'status' => 'active',
        ]);

        // Create a student in CS department without account
        $this->studentCsNoAccount = Student::create([
            'user_fk_id' => null,
            'student_id' => '2025-00003',
            'first_name' => 'Student',
            'last_name' => 'CS No Account',
            'sex' => Sex::Male,
            'program_id' => $this->programBsit->id,
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
    public function view_any_registrar_allowed()
    {
        $this->assertTrue($this->registrar->can('viewAny', Student::class));
    }

    /** @test */
    public function view_any_admin_allowed()
    {
        $this->assertTrue($this->admin->can('viewAny', Student::class));
    }

    /** @test */
    public function view_any_accounting_denied()
    {
        $this->assertFalse($this->accounting->can('viewAny', Student::class));
    }

    /** @test */
    public function view_any_faculty_denied()
    {
        $this->assertFalse($this->faculty->can('viewAny', Student::class));
    }

    /** @test */
    public function view_any_student_denied()
    {
        $this->assertFalse($this->studentUser->can('viewAny', Student::class));
    }

    /** @test */
    public function view_registrar_in_department_allowed()
    {
        $this->assertTrue($this->registrar->can('view', $this->studentCs));
    }

    /** @test */
    public function view_registrar_out_of_department_denied()
    {
        $this->assertFalse($this->registrar->can('view', $this->studentBus));
    }

    /** @test */
    public function view_admin_always_allowed()
    {
        $this->assertTrue($this->admin->can('view', $this->studentCs));
        $this->assertTrue($this->admin->can('view', $this->studentBus));
    }

    /** @test */
    public function view_accounting_denied()
    {
        $this->assertFalse($this->accounting->can('view', $this->studentCs));
        $this->assertFalse($this->accounting->can('view', $this->studentBus));
    }

    /** @test */
    public function view_faculty_denied()
    {
        $this->assertFalse($this->faculty->can('view', $this->studentCs));
        $this->assertFalse($this->faculty->can('view', $this->studentBus));
    }

    /** @test */
    public function view_student_own_record_allowed()
    {
        $this->assertTrue($this->studentUser->can('view', $this->studentCs));
    }

    /** @test */
    public function view_student_other_record_denied()
    {
        $this->assertFalse($this->studentUser->can('view', $this->studentBus));
    }

    /** @test */
    public function create_registrar_allowed()
    {
        $this->assertTrue($this->registrar->can('create', Student::class));
    }

    /** @test */
    public function create_admin_allowed()
    {
        $this->assertTrue($this->admin->can('create', Student::class));
    }

    /** @test */
    public function create_accounting_denied()
    {
        $this->assertFalse($this->accounting->can('create', Student::class));
    }

    /** @test */
    public function create_faculty_denied()
    {
        $this->assertFalse($this->faculty->can('create', Student::class));
    }

    /** @test */
    public function create_student_denied()
    {
        $this->assertFalse($this->studentUser->can('create', Student::class));
    }

    /** @test */
    public function update_registrar_in_department_allowed()
    {
        $this->assertTrue($this->registrar->can('update', $this->studentCs));
    }

    /** @test */
    public function update_registrar_out_of_department_denied()
    {
        $this->assertFalse($this->registrar->can('update', $this->studentBus));
    }

    /** @test */
    public function update_admin_always_allowed()
    {
        $this->assertTrue($this->admin->can('update', $this->studentCs));
        $this->assertTrue($this->admin->can('update', $this->studentBus));
    }

    /** @test */
    public function update_accounting_denied()
    {
        $this->assertFalse($this->accounting->can('update', $this->studentCs));
    }

    /** @test */
    public function update_faculty_denied()
    {
        $this->assertFalse($this->faculty->can('update', $this->studentCs));
    }

    /** @test */
    public function update_student_denied()
    {
        $this->assertFalse($this->studentUser->can('update', $this->studentCs));
    }

    /** @test */
    public function create_account_registrar_in_department_no_account_allowed()
    {
        // studentCsNoAccount is in registrar's department (CS) and has no account
        $this->assertTrue($this->registrar->can('createAccount', $this->studentCsNoAccount));
    }

    /** @test */
    public function create_account_registrar_in_department_with_account_denied()
    {
        // studentCs has account, in registrar's dept
        $this->assertFalse($this->registrar->can('createAccount', $this->studentCs));
    }

    /** @test */
    public function create_account_registrar_out_of_department_denied()
    {
        // studentBus has no account, out of registrar's dept
        $this->assertFalse($this->registrar->can('createAccount', $this->studentBus));
    }

    /** @test */
    public function create_account_admin_allowed_if_no_account()
    {
        // admin can always check
        $this->assertTrue($this->admin->can('createAccount', $this->studentBus));
        $this->assertFalse($this->admin->can('createAccount', $this->studentCs)); // has account
    }

    /** @test */
    public function create_account_accounting_denied()
    {
        $this->assertFalse($this->accounting->can('createAccount', $this->studentCs));
    }

    /** @test */
    public function create_account_faculty_denied()
    {
        $this->assertFalse($this->faculty->can('createAccount', $this->studentCs));
    }

    /** @test */
    public function create_account_student_denied()
    {
        $this->assertFalse($this->studentUser->can('createAccount', $this->studentCs));
    }

    /** @test */
    public function reset_password_registrar_in_department_allowed()
    {
        $this->assertTrue($this->registrar->can('resetPassword', $this->studentCs));
    }

    /** @test */
    public function reset_password_registrar_out_of_department_denied()
    {
        $this->assertFalse($this->registrar->can('resetPassword', $this->studentBus));
    }

    /** @test */
    public function reset_password_admin_allowed()
    {
        $this->assertTrue($this->admin->can('resetPassword', $this->studentCs));
        $this->assertTrue($this->admin->can('resetPassword', $this->studentBus));
    }

    /** @test */
    public function reset_password_accounting_denied()
    {
        $this->assertFalse($this->accounting->can('resetPassword', $this->studentCs));
    }

    /** @test */
    public function reset_password_faculty_denied()
    {
        $this->assertFalse($this->faculty->can('resetPassword', $this->studentCs));
    }

    /** @test */
    public function reset_password_student_denied()
    {
        $this->assertFalse($this->studentUser->can('resetPassword', $this->studentCs));
    }

    /** @test */
    public function set_status_registrar_in_department_allowed()
    {
        $this->assertTrue($this->registrar->can('setStatus', $this->studentCs));
    }

    /** @test */
    public function set_status_registrar_out_of_department_denied()
    {
        $this->assertFalse($this->registrar->can('setStatus', $this->studentBus));
    }

    /** @test */
    public function set_status_admin_allowed()
    {
        $this->assertTrue($this->admin->can('setStatus', $this->studentCs));
        $this->assertTrue($this->admin->can('setStatus', $this->studentBus));
    }

    /** @test */
    public function set_status_accounting_denied()
    {
        $this->assertFalse($this->accounting->can('setStatus', $this->studentCs));
    }

    /** @test */
    public function set_status_faculty_denied()
    {
        $this->assertFalse($this->faculty->can('setStatus', $this->studentCs));
    }

    /** @test */
    public function set_status_student_denied()
    {
        $this->assertFalse($this->studentUser->can('setStatus', $this->studentCs));
    }
}