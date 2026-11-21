<?php

namespace Database\Seeders;

use App\Enums\Sex;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only run in non-production environments
        if (app()->isProduction()) {
            $this->command->warn('DemoSeeder skipped: running in production environment.');
            return;
        }

        // Create 2 departments
        $deptCs = Department::create([
            'code' => 'CS',
            'name' => 'College of Computer Studies',
        ]);

        $deptBusiness = Department::create([
            'code' => 'BUS',
            'name' => 'College of Business Administration',
        ]);

        // Create 2 programs
        $programBsit = Program::create([
            'department_id' => $deptCs->id,
            'code' => 'BSIT',
            'name' => 'Bachelor of Science in Information Technology',
            'years_to_complete' => 4,
        ]);

        $programBsa = Program::create([
            'department_id' => $deptBusiness->id,
            'code' => 'BSA',
            'name' => 'Bachelor of Science in Accountancy',
            'years_to_complete' => 4,
        ]);

        // Create admin user
        $adminUser = User::create([
            'user_id' => 'EMP-0001',
            'email' => 'admin@school.edu',
            'password' => Hash::make('password'),
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $adminUser->assignRole('admin');

        $adminEmployee = Employee::create([
            'user_fk_id' => $adminUser->id,
            'employee_id' => 'EMP-0001',
            'first_name' => 'System',
            'middle_name' => null,
            'last_name' => 'Administrator',
            'sex' => Sex::Male,
            'contact_number' => '09170000001',
            'position' => 'Administrator',
        ]);

        // Create registrar user (assigned to CS department only)
        $registrarUser = User::create([
            'user_id' => 'EMP-0002',
            'email' => 'registrar@school.edu',
            'password' => Hash::make('password'),
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $registrarUser->assignRole('registrar');

        $registrarEmployee = Employee::create([
            'user_fk_id' => $registrarUser->id,
            'employee_id' => 'EMP-0002',
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'last_name' => 'Cruz',
            'sex' => Sex::Female,
            'contact_number' => '09171234567',
            'position' => 'Registrar',
        ]);
        $registrarEmployee->departments()->attach($deptCs->id);

        // Create accounting user
        $accountingUser = User::create([
            'user_id' => 'EMP-0003',
            'email' => 'accounting@school.edu',
            'password' => Hash::make('password'),
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $accountingUser->assignRole('accounting');

        $accountingEmployee = Employee::create([
            'user_fk_id' => $accountingUser->id,
            'employee_id' => 'EMP-0003',
            'first_name' => 'Juan',
            'middle_name' => 'Dela',
            'last_name' => 'Cruz',
            'sex' => Sex::Male,
            'contact_number' => '09181234567',
            'position' => 'Accounting Officer',
        ]);
        $accountingEmployee->departments()->attach($deptBusiness->id);

        // Create faculty user
        $facultyUser = User::create([
            'user_id' => 'EMP-0004',
            'email' => 'faculty@school.edu',
            'password' => Hash::make('password'),
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $facultyUser->assignRole('faculty');

        $facultyEmployee = Employee::create([
            'user_fk_id' => $facultyUser->id,
            'employee_id' => 'EMP-0004',
            'first_name' => 'Ana',
            'middle_name' => 'Reyes',
            'last_name' => 'Garcia',
            'sex' => Sex::Female,
            'contact_number' => '09191234567',
            'position' => 'Instructor',
        ]);
        $facultyEmployee->departments()->attach($deptCs->id);

        // Create 3 students WITHOUT accounts (user_fk_id = null)
        Student::create([
            'user_fk_id' => null,
            'student_id' => '2025-00001',
            'first_name' => 'Carlos',
            'middle_name' => 'Miguel',
            'last_name' => 'Santos',
            'sex' => Sex::Male,
            'contact_number' => '09201234567',
            'program_id' => $programBsit->id,
            'year_level' => 1,
            'status' => 'active',
        ]);

        Student::create([
            'user_fk_id' => null,
            'student_id' => '2025-00002',
            'first_name' => 'Sofia',
            'middle_name' => 'Isabel',
            'last_name' => 'Reyes',
            'sex' => Sex::Female,
            'contact_number' => '09211234567',
            'program_id' => $programBsit->id,
            'year_level' => 1,
            'status' => 'active',
        ]);

        Student::create([
            'user_fk_id' => null,
            'student_id' => '2025-00003',
            'first_name' => 'Miguel',
            'middle_name' => 'Antonio',
            'last_name' => 'Garcia',
            'sex' => Sex::Male,
            'contact_number' => '09221234567',
            'program_id' => $programBsa->id,
            'year_level' => 1,
            'status' => 'active',
        ]);

        $this->command->info('Demo data seeded successfully.');
    }
}