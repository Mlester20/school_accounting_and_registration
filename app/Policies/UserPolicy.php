<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function assignRole(User $actor, UserRole $role): bool
    {
        if ($actor->isAdmin()) {
            return true;
        }

        return match ($role) {
            UserRole::Student => $actor->can('accounts.student.create'),
            UserRole::Faculty => $actor->can('accounts.faculty.create'),
            UserRole::Registrar, UserRole::Accounting => $actor->can('accounts.staff.create'),
            default => false,
        };
    }
}