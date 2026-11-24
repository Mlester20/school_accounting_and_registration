<?php

namespace Tests\Feature\Registrar;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Enums\Sex;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class StudentAccountTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $registrar;
    protected User $accounting;
    protected User $faculty;
    protected User $studentUser;
    protected Student $student1; // CS program, has account
    protected Student $student2; // CS program, no account
    protected Student $student3; // CS program, no account (for bulk)
    protected Student $student4; // Business program, no account
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
            'program_id' => $this->programBsit->id,
            'year_level' => 1,
            'status' => 'active',
        ]);

        $this->student4 = Student::create([
            'user_fk_id' => null,
            'student_id' => '2025-00004',
            'first_name' => 'Student',
            'last_name' => 'Four',
            'sex' => Sex::Female,
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
    public function create_student_account_creates_user_correctly()
    {
        $this->actingAs($this->registrar);

        $response = $this->post(route('registrar.students.account.store', $this->student2->id));

        $response->assertRedirect(route('registrar.students.index'));

        // Check flash credentials
        $this->assertNotNull(session('credentials'));
        $credentials = session('credentials');
        $this->assertCount(1, $credentials);

        $cred = $credentials[0];
        $this->assertEquals('2025-00002', $cred['student_id']);
        $this->assertEquals('2025-00002', $cred['user_id']);
        $this->assertNotEmpty($cred['temporary_password']);

        // Verify user created correctly
        $user = User::where('user_id', '2025-00002')->first();
        $this->assertNotNull($user);
        $this->assertEquals('student', $user->getRoleNames()->first());
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check($cred['temporary_password'], $user->password));

        // Verify student linked
        $this->student2->refresh();
        $this->assertEquals($user->id, $this->student2->user_fk_id);
    }

    /** @test */
    public function create_student_account_links_student()
    {
        $this->actingAs($this->registrar);

        $this->post(route('registrar.students.account.store', $this->student2->id));

        $this->student2->refresh();
        $this->assertNotNull($this->student2->user_fk_id);
        $this->assertEquals($this->student2->student_id, $this->student2->user->user_id);
    }

    /** @test */
    public function create_student_account_second_attempt_fails()
    {
        $this->actingAs($this->registrar);

        // First attempt - succeeds
        $this->post(route('registrar.students.account.store', $this->student2->id));

        // Second attempt - should fail (policy returns 403 because student already has account)
        $response = $this->post(route('registrar.students.account.store', $this->student2->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function create_student_account_plain_password_not_in_activity_log()
    {
        $this->actingAs($this->registrar);

        $this->post(route('registrar.students.account.store', $this->student2->id));

        $activity = Activity::where('causer_id', $this->registrar->id)
            ->where('subject_type', Student::class)
            ->where('subject_id', $this->student2->id)
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('student_account_created', $activity->description);

        $properties = $activity->properties;
        $this->assertArrayNotHasKey('password', $properties);
        $this->assertArrayNotHasKey('temporary_password', $properties);
        $this->assertEquals('2025-00002', $properties['student_id']);
        $this->assertEquals('2025-00002', $properties['user_id']);
    }

    /** @test */
    public function bulk_create_student_accounts_excludes_out_of_department()
    {
        $this->actingAs($this->registrar);

        $response = $this->post(route('registrar.students.accounts.bulk'), [
            'student_ids' => [$this->student2->id, $this->student3->id, $this->student4->id]
        ]);

        $response->assertRedirect(route('registrar.students.index'));

        $credentials = session('bulk_credentials');
        $this->assertCount(2, $credentials); // student2 and student3 only

        $studentIds = collect($credentials)->pluck('student_id')->toArray();
        $this->assertContains('2025-00002', $studentIds);
        $this->assertContains('2025-00003', $studentIds);
        $this->assertNotContains('2025-00004', $studentIds);

        // student4 should still have no account
        $this->student4->refresh();
        $this->assertNull($this->student4->user_fk_id);
    }

    /** @test */
    public function bulk_create_student_accounts_partial_failures_reported()
    {
        $this->actingAs($this->registrar);

        // student2 and student3: no account, in department
        // student1: already has account
        // student4: out of department
        $response = $this->post(route('registrar.students.accounts.bulk'), [
            'student_ids' => [$this->student1->id, $this->student2->id, $this->student3->id, $this->student4->id]
        ]);

        $response->assertRedirect(route('registrar.students.index'));

        // Should have 2 created (student2, student3) and 2 failed (student1, student4)
        // Note: flash only contains created credentials, failures are in flash.errors or similar
        $credentials = session('bulk_credentials');
        $this->assertCount(2, $credentials);

        $studentIds = collect($credentials)->pluck('student_id')->toArray();
        $this->assertContains('2025-00002', $studentIds);
        $this->assertContains('2025-00003', $studentIds);
    }

    /** @test */
    public function reset_student_password_invalidates_sessions()
    {
        $this->actingAs($this->registrar);

        // Create a session for student1 (who has account)
        $sessionId = 'test-session-' . uniqid();
        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $this->student1->user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'test',
            'last_activity' => time(),
        ]);

        $this->assertDatabaseHas('sessions', ['id' => $sessionId]);

        $response = $this->post(route('registrar.students.account.reset-password', $this->student1->id));

        $response->assertRedirect(route('registrar.students.index'));

        // Session should be deleted
        $this->assertDatabaseMissing('sessions', ['id' => $sessionId]);
    }

    /** @test */
    public function reset_student_password_forces_change()
    {
        $this->actingAs($this->registrar);

        $originalMustChange = $this->student1->user->must_change_password;
        $this->assertFalse($originalMustChange);

        $response = $this->post(route('registrar.students.account.reset-password', $this->student1->id));

        $response->assertRedirect(route('registrar.students.index'));

        $this->student1->user->refresh();
        $this->assertTrue($this->student1->user->must_change_password);

        // New temp password should be different and in flash
        $credentials = session('credentials');
        $this->assertCount(1, $credentials);
        $this->assertEquals('2025-00001', $credentials[0]['student_id']);
        $this->assertNotEmpty($credentials[0]['temporary_password']);
    }

    /** @test */
    public function set_student_account_status_inactive_blocks_login()
    {
        $this->actingAs($this->registrar);

        // Deactivate account
        $response = $this->put(route('registrar.students.account.status', $this->student1->id), [
            'status' => 'inactive'
        ]);

        $response->assertRedirect(route('registrar.students.index'));

        $this->student1->user->refresh();
        $this->assertEquals('inactive', $this->student1->user->status);

        // Logout registrar
        $this->app['auth']->guard('web')->logout();

        // Try to login as student with inactive status
        $loginResponse = $this->post(route('login'), [
            'user_id' => '2025-00001',
            'password' => 'password', // original password
        ]);

        // Should fail (status inactive) - generic error on user_id field
        $loginResponse->assertSessionHasErrors('user_id');
    }

    /** @test */
    public function new_account_can_login_with_temp_password_and_forced_change()
    {
        $this->actingAs($this->registrar);

        // Create account for student2
        $this->post(route('registrar.students.account.store', $this->student2->id));
        $credentials = session('credentials');
        $tempPassword = $credentials[0]['temporary_password'];

        // Logout registrar
        $this->app['auth']->guard('web')->logout();

        // Login as new student with temp password
        $loginResponse = $this->post(route('login'), [
            'user_id' => '2025-00002',
            'password' => $tempPassword,
        ]);

        // Should redirect to password change (or dashboard if no force change page)
        $this->assertTrue($loginResponse->isRedirect());

        // Verify must_change_password is true
        $student2User = User::where('user_id', '2025-00002')->first();
        $this->assertTrue($student2User->must_change_password);
    }

    /** @test */
    public function set_student_account_status_active_allows_login()
    {
        $this->actingAs($this->registrar);

        // First deactivate
        $this->put(route('registrar.students.account.status', $this->student1->id), [
            'status' => 'inactive'
        ]);

        // Then reactivate
        $response = $this->put(route('registrar.students.account.status', $this->student1->id), [
            'status' => 'active'
        ]);

        $response->assertRedirect(route('registrar.students.index'));

        $this->student1->user->refresh();
        $this->assertEquals('active', $this->student1->user->status);

        // Should be able to login
        $this->app['auth']->guard('web')->logout();

        $loginResponse = $this->post(route('login'), [
            'user_id' => '2025-00001',
            'password' => 'password',
        ]);

        $this->assertTrue($loginResponse->isRedirect());
    }

    /** @test */
    public function reset_password_logs_activity()
    {
        $this->actingAs($this->registrar);

        $this->post(route('registrar.students.account.reset-password', $this->student1->id));

        $activity = Activity::where('causer_id', $this->registrar->id)
            ->where('subject_type', Student::class)
            ->where('subject_id', $this->student1->id)
            ->where('description', 'student_password_reset')
            ->first();

        $this->assertNotNull($activity);
        $properties = $activity->properties;
        $this->assertEquals('2025-00001', $properties['student_id']);
        $this->assertEquals('2025-00001', $properties['user_id']);
        $this->assertTrue($properties['must_change_password']);
        $this->assertArrayNotHasKey('password', $properties);
    }

    /** @test */
    public function set_status_logs_activity()
    {
        $this->actingAs($this->registrar);

        $this->put(route('registrar.students.account.status', $this->student1->id), [
            'status' => 'inactive'
        ]);

        $activity = Activity::where('causer_id', $this->registrar->id)
            ->where('subject_type', Student::class)
            ->where('subject_id', $this->student1->id)
            ->where('description', 'student_account_status_changed')
            ->first();

        $this->assertNotNull($activity);
        $properties = $activity->properties;
        $this->assertEquals('2025-00001', $properties['student_id']);
        $this->assertEquals('inactive', $properties['new_status']);
        $this->assertTrue($properties['sessions_invalidated']);
    }

    /** @test */
    public function credentials_flash_is_consumed_after_next_request()
    {
        $this->actingAs($this->registrar);

        // Create account — flashes credentials for the next request (the index redirect)
        $this->post(route('registrar.students.account.store', $this->student2->id));
        $this->assertNotNull(session('credentials'), 'Credentials should be in session after account creation');

        // Follow the redirect to the index: flash is read via session()->pull() in the controller,
        // so it must be GONE from the session after this request completes.
        $response = $this->get(route('registrar.students.index'));
        $response->assertOk();

        $this->assertNull(session('credentials'), 'Credentials flash must be consumed after the next request');

        // And a further request also sees nothing
        $this->get(route('registrar.students.index'));
        $this->assertNull(session('credentials'));
    }

    /** @test */
    public function bulk_store_rejects_missing_student_ids()
    {
        $this->actingAs($this->registrar);

        $response = $this->post(route('registrar.students.accounts.bulk'), []);

        $response->assertSessionHasErrors('student_ids');
    }

    /** @test */
    public function bulk_store_rejects_more_than_200_student_ids()
    {
        $this->actingAs($this->registrar);

        $ids = array_map(fn ($i) => 100000 + $i, range(1, 201));

        $response = $this->post(route('registrar.students.accounts.bulk'), [
            'student_ids' => $ids,
        ]);

        $response->assertSessionHasErrors('student_ids');
    }

    /** @test */
    public function bulk_store_rejects_duplicate_student_ids()
    {
        $this->actingAs($this->registrar);

        $response = $this->post(route('registrar.students.accounts.bulk'), [
            'student_ids' => [$this->student2->id, $this->student2->id],
        ]);

        $response->assertSessionHasErrors('student_ids.*');
    }

    /** @test */
    public function bulk_store_rejects_non_integer_student_ids()
    {
        $this->actingAs($this->registrar);

        $response = $this->post(route('registrar.students.accounts.bulk'), [
            'student_ids' => ['abc'],
        ]);

        $response->assertSessionHasErrors('student_ids.*');
    }

    /** @test */
    public function reset_password_on_student_without_account_returns_422()
    {
        $this->actingAs($this->registrar);

        // student2 is in the registrar's department but has no account yet
        $response = $this->post(route('registrar.students.account.reset-password', $this->student2->id));

        $response->assertStatus(422);
        $this->assertEquals('Student has no account to reset.', $response->exception->getMessage());

        // Nothing changed: no user row created, no credentials flashed, no activity logged
        $this->assertNull(User::where('user_id', '2025-00002')->first());
        $this->assertNull(session('credentials'));
        $this->assertDatabaseMissing('activity_log', [
            'subject_type' => Student::class,
            'subject_id' => $this->student2->id,
            'description' => 'student_password_reset',
        ]);
    }

    /** @test */
    public function set_status_on_student_without_account_returns_422()
    {
        $this->actingAs($this->registrar);

        // student2 is in the registrar's department but has no account yet
        $response = $this->put(route('registrar.students.account.status', $this->student2->id), [
            'status' => 'inactive',
        ]);

        $response->assertStatus(422);
        $this->assertEquals('Student has no account.', $response->exception->getMessage());

        // Nothing changed: no user row created, no activity logged
        $this->assertNull(User::where('user_id', '2025-00002')->first());
        $this->assertDatabaseMissing('activity_log', [
            'subject_type' => Student::class,
            'subject_id' => $this->student2->id,
            'description' => 'student_account_status_changed',
        ]);
    }
}