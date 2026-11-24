<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return $user->can('students.view');
        }
        if ($user->isRegistrar()) {
            return $user->can('students.view');
        }
        return false;
    }

    public function view(User $user, Student $student): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isStudent()) {
            return $student->user_fk_id === $user->id;
        }
        if ($user->isRegistrar()) {
            return $user->can('students.view')
                && $student->isInDepartments($user->departmentIds());
        }
        return false;
    }

    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isRegistrar()) {
            return $user->can('students.manage');
        }
        return false;
    }

    public function update(User $user, Student $student): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isRegistrar()) {
            return $user->can('students.manage')
                && $student->isInDepartments($user->departmentIds());
        }
        return false;
    }

    public function createAccount(User $user, Student $student): bool
    {
        if ($user->isAdmin()) {
            return $student->user_fk_id === null;
        }
        if ($user->isRegistrar()) {
            return $user->can('accounts.student.create')
                && $student->isInDepartments($user->departmentIds())
                && $student->user_fk_id === null;
        }
        return false;
    }

    // Note: account-state checks (whether the student has an account) are enforced
    // inside the Actions (row-locked, aborts 422 with a clear message), not here.
    // The policy only checks permission + department scope.
    public function resetPassword(User $user, Student $student): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isRegistrar()) {
            return $user->can('accounts.student.manage')
                && $student->isInDepartments($user->departmentIds());
        }
        return false;
    }

    public function setStatus(User $user, Student $student): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isRegistrar()) {
            return $user->can('accounts.student.manage')
                && $student->isInDepartments($user->departmentIds());
        }
        return false;
    }

    public function enroll(User $user, Student $student): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isRegistrar()) {
            return $user->can('enrollment.manage')
                && $student->isInDepartments($user->departmentIds());
        }
        return false;
    }
}