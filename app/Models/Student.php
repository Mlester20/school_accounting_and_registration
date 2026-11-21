<?php

namespace App\Models;

use App\Enums\Sex;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_fk_id',
        'student_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'sex',
        'contact_number',
        'program_id',
        'year_level',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sex' => Sex::class,
            'year_level' => 'integer',
        ];
    }

    /**
     * Get the user associated with the student.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_fk_id');
    }

    /**
     * Get the program associated with the student.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the department through the program.
     */
    public function department(): BelongsTo
    {
        return $this->program->department();
    }

    /**
     * Scope to filter students visible to a specific user.
     *
     * Rules:
     * - Admin and Accounting: see all students
     * - Registrar: see only students whose program belongs to their assigned departments
     * - Student: see only themselves
     * - Faculty and others: see none
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isAdmin() || $user->isAccounting()) {
            return $query;
        }

        if ($user->isRegistrar()) {
            $departmentIds = $user->departmentIds();
            if (empty($departmentIds)) {
                return $query->whereRaw('1 = 0');
            }
            return $query->whereHas('program', function ($q) use ($departmentIds) {
                $q->whereIn('department_id', $departmentIds);
            });
        }

        if ($user->isStudent()) {
            return $query->where('user_fk_id', $user->id);
        }

        // Faculty and others see nothing
        return $query->whereRaw('1 = 0');
    }

    /**
     * Get the student's full name.
     */
    public function getFullNameAttribute(): string
    {
        $parts = [$this->first_name];
        if ($this->middle_name) {
            $parts[] = $this->middle_name;
        }
        $parts[] = $this->last_name;
        if ($this->suffix) {
            $parts[] = $this->suffix;
        }
        return implode(' ', $parts);
    }
}