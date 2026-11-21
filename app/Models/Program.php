<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'code',
        'name',
        'years_to_complete',
    ];

    protected function casts(): array
    {
        return [
            'years_to_complete' => 'integer',
        ];
    }

    /**
     * Get the department associated with the program.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the students enrolled in the program.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}