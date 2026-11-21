<?php

namespace App\Models;

use App\Enums\Sex;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_fk_id',
        'employee_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'sex',
        'contact_number',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'sex' => Sex::class,
        ];
    }

    /**
     * Get the user associated with the employee.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_fk_id');
    }

    /**
     * Get the departments associated with the employee.
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_employee')
            ->withTimestamps();
    }

    /**
     * Get the employee's full name.
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