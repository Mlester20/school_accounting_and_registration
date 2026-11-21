<?php

namespace Tests\Feature\Auth;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Student;
use App\Models\User;
use App\Enums\Sex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class StaffLoginAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::create(['name' => 'registrar', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => 'student', 'guard_name' => 'web']);

        $this->deptCs = Department::create(['code' => 'CS', 'name' => 'College of Computer Studies']);
        $this->programBsit = \App\Models\Program::create([
            'department_id' => $this->deptCs->id,
            'code' => 'BSIT',
            'name' => 'Bachelor of Science in Information Technology',
            'years_to_complete' => 4,
        ]);
    }

    private function createRegistrarUser(): User
    {
        $user = User::factory()->create([
            'user_id' => 'EMP-0002',
            'email' => 'registrar@test.edu',
            'password' => Hash::make('password'),
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $user->assignRole('registrar');

        Employee::create([
            'user_fk_id' => $user->id,
            'employee_id' => 'EMP-0002',
            'first_name' => 'Maria',
            'last_name' => 'Cruz',
            'sex' => Sex::Female,
            'contact_number' => '09171234567',
            'position' => 'Registrar',
        ])->departments()->attach($this->deptCs->id);

        return $user;
    }

    private function createStudentUser(): User
    {
        $user = User::factory()->create([
            'user_id' => '2025-00001',
            'email' => 'student@test.edu',
            'password' => Hash::make('password'),
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $user->assignRole('student');

        Student::create([
            'user_fk_id' => $user->id,
            'student_id' => '2025-00001',
            'first_name' => 'Carlos',
            'last_name' => 'Santos',
            'sex' => Sex::Male,
            'contact_number' => '09201234567',
            'program_id' => $this->programBsit->id,
            'year_level' => 1,
            'status' => 'active',
        ]);

        return $user;
    }

    /** @test */
    public function staff_login_creates_exactly_one_staff_login_activity_row(): void
    {
        $registrar = $this->createRegistrarUser();

        $response = $this->post('/login', [
            'user_id' => 'EMP-0002',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($registrar);

        $activities = Activity::where('description', 'staff login')->get();

        $this->assertCount(1, $activities);
        $this->assertEquals($registrar->id, $activities->first()->causer_id);
        $this->assertEquals(User::class, $activities->first()->causer_type);
        $this->assertEquals($registrar->id, $activities->first()->subject_id);
        $this->assertEquals(User::class, $activities->first()->subject_type);
        $this->assertEquals('EMP-0002', $activities->first()->properties['user_id']);
    }

    /** @test */
    public function student_login_creates_no_staff_login_activity_row(): void
    {
        $student = $this->createStudentUser();

        $response = $this->post('/login', [
            'user_id' => '2025-00001',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($student);

        $this->assertCount(0, Activity::where('description', 'staff login')->get());
        $this->assertCount(0, Activity::all());
    }

    /** @test */
    public function no_activity_row_contains_the_plain_password(): void
    {
        $registrar = $this->createRegistrarUser();

        $this->post('/login', [
            'user_id' => 'EMP-0002',
            'password' => 'password',
        ]);

        foreach (Activity::all() as $activity) {
            $serialized = json_encode($activity->properties->toArray());
            $this->assertStringNotContainsString('password', $serialized);
        }

        // Also verify the failed-login path (wrong password) logs nothing containing it
        $this->post('/login', [
            'user_id' => 'EMP-0002',
            'password' => 'super-secret-wrong-password',
        ]);

        foreach (Activity::all() as $activity) {
            $serialized = json_encode($activity->properties->toArray());
            $this->assertStringNotContainsString('super-secret-wrong-password', $serialized);
        }
    }
}
