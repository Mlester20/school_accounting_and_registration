<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions
        $permissions = [
            'students.view',
            'students.manage',
            'students.import',
            'accounts.student.create',
            'accounts.faculty.create',
            'accounts.staff.create',
            'enrollment.manage',
            'academic.manage',
            'fees.manage',
            'payments.encode',
            'payments.void',
            'grades.encode',
            'users.manage',
            'audit.view',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles with permissions
        $roles = [
            'admin' => $permissions, // Admin gets all permissions
            'registrar' => [
                'students.view',
                'students.manage',
                'students.import',
                'accounts.student.create',
                'accounts.faculty.create',
                'enrollment.manage',
                'academic.manage',
                'audit.view',
            ],
            'accounting' => [
                'students.view',
                'fees.manage',
                'payments.encode',
                'payments.void',
                'audit.view',
            ],
            'faculty' => [
                'grades.encode',
                'audit.view',
            ],
            'student' => [
                // Students have no special permissions by default
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }

        $this->command->info('Roles and permissions seeded successfully.');
    }
}