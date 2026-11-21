<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'email',
        'password',
        'status',
        'must_change_password',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get the student associated with the user.
     */
    public function student()
    {
        return $this->hasOne(Student::class, 'user_fk_id', 'id');
    }

    /**
     * Get the employee associated with the user.
     */
    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_fk_id', 'id');
    }

    /**
     * Get the department IDs associated with the user (via employee).
     */
    public function departmentIds(): array
    {
        if ($this->employee) {
            return $this->employee->departments->pluck('id')->toArray();
        }
        return [];
    }

    /**
     * Check if user has a specific role (using Spatie's hasRole).
     */
    public function hasUserRole(UserRole|string $role): bool
    {
        return $this->hasRole($role instanceof UserRole ? $role->value : $role);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasUserRole(UserRole::Admin);
    }

    /**
     * Check if user is registrar.
     */
    public function isRegistrar(): bool
    {
        return $this->hasUserRole(UserRole::Registrar);
    }

    /**
     * Check if user is accounting.
     */
    public function isAccounting(): bool
    {
        return $this->hasUserRole(UserRole::Accounting);
    }

    /**
     * Check if user is faculty.
     */
    public function isFaculty(): bool
    {
        return $this->hasUserRole(UserRole::Faculty);
    }

    /**
     * Check if user is student.
     */
    public function isStudent(): bool
    {
        return $this->hasUserRole(UserRole::Student);
    }

    /**
     * Get the user's full name from student or employee relation.
     */
    public function fullName(): string
    {
        if ($this->student) {
            return $this->student->full_name;
        }
        if ($this->employee) {
            return $this->employee->full_name;
        }
        return $this->user_id;
    }
}