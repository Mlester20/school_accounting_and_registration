<?php

namespace Tests\Feature;

use App\Enums\Sex;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $registrar;
    protected User $accounting;
    protected User $faculty;
    protected User $studentUser;
    protected Student $student1;
    protected Student $student2;
    protected Student $student3;
    protected Department $deptCs;
    protected Department $deptBusiness;
    protected Program $programBsit;
    protected Program $programBsa;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'registrar', 'guard_name' => 'web']);
        Role::create(['name' => 'accounting', 'guard_name' => 'web']);
        Role::create(['name' => 'faculty', 'guard_name' => 'web']);
        Role::create(['name' => 'student', 'guard_name' => 'web']);

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
            'employee_id' => 'EMP-0001',
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
            'employee_id' => 'EMP-0002',
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
            'employee_id' => 'EMP-0003',
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
        // Student 1: CS program, has user account
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

        // Student 2: CS program, no user account
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

        // Student 3: Business program, no user account
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

    /** @test */
    public function admin_sees_all_students()
    {
        $students = Student::visibleTo($this->admin)->get();
        $this->assertCount(3, $students);
    }

    /** @test */
    public function accounting_sees_all_students()
    {
        $students = Student::visibleTo($this->accounting)->get();
        $this->assertCount(3, $students);
    }

    /** @test */
    public function registrar_sees_only_students_in_assigned_departments()
    {
        // Registrar is assigned to CS department only
        $students = Student::visibleTo($this->registrar)->get();

        // Should see student1 and student2 (both in CS program), but not student3 (in Business)
        $this->assertCount(2, $students);
        $this->assertTrue($students->contains('student_id', '2025-00001'));
        $this->assertTrue($students->contains('student_id', '2025-00002'));
        $this->assertFalse($students->contains('student_id', '2025-00003'));
    }

    /** @test */
    public function student_sees_only_themselves()
    {
        $students = Student::visibleTo($this->studentUser)->get();

        $this->assertCount(1, $students);
        $this->assertEquals('2025-00001', $students->first()->student_id);
    }

    /** @test */
    public function faculty_sees_no_students()
    {
        $students = Student::visibleTo($this->faculty)->get();
        $this->assertCount(0, $students);
    }

    /** @test */
    public function student_without_user_account_can_exist()
    {
        // student2 and student3 have user_fk_id = null
        $this->assertNull($this->student2->user_fk_id);
        $this->assertNull($this->student3->user_fk_id);
        $this->assertEquals('2025-00002', $this->student2->student_id);
        $this->assertEquals('2025-00003', $this->student3->student_id);
    }

    /** @test */
    public function student_program_department_relations_work()
    {
        // student1 -> program -> department
        $this->assertEquals($this->programBsit->id, $this->student1->program_id);
        $this->assertEquals($this->deptCs->id, $this->student1->program->department_id);

        // student3 -> program -> department
        $this->assertEquals($this->programBsa->id, $this->student3->program_id);
        $this->assertEquals($this->deptBusiness->id, $this->student3->program->department_id);
    }

    /** @test */
    public function employee_department_pivot_works()
    {
        // Registrar employee is attached to CS department
        $this->registrar->load('employee.departments');
        $this->assertCount(1, $this->registrar->employee->departments);
        $this->assertEquals($this->deptCs->id, $this->registrar->employee->departments->first()->id);

        // Accounting employee is attached to Business department
        $this->accounting->load('employee.departments');
        $this->assertCount(1, $this->accounting->employee->departments);
        $this->assertEquals($this->deptBusiness->id, $this->accounting->employee->departments->first()->id);
    }

    /** @test */
    public function user_department_ids_helper_works()
    {
        $this->assertEquals([$this->deptCs->id], $this->registrar->departmentIds());
        $this->assertEquals([$this->deptBusiness->id], $this->accounting->departmentIds());
        $this->assertEquals([], $this->admin->departmentIds()); // Admin has no employee
        $this->assertEquals([], $this->studentUser->departmentIds()); // Student has no employee
    }

    /** @test */
    public function registrar_with_no_departments_sees_no_students()
    {
        // Create a registrar with no department assignments
        $registrarNoDept = User::create([
            'user_id' => 'REG-0002',
            'email' => 'registrar2@test.edu',
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $registrarNoDept->assignRole('registrar');

        $emp = Employee::create([
            'user_fk_id' => $registrarNoDept->id,
            'employee_id' => 'EMP-0004',
            'first_name' => 'Test',
            'last_name' => 'Registrar2',
            'sex' => Sex::Male,
            'contact_number' => '09201234567',
            'position' => 'Registrar',
        ]);
        // No departments attached

        $students = Student::visibleTo($registrarNoDept)->get();
        $this->assertCount(0, $students);
    }
}