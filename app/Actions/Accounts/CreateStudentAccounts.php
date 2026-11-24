<?php

namespace App\Actions\Accounts;

use App\Models\Student;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CreateStudentAccounts
{
    public function handle(User $actor, array $studentIds): array
    {
        $students = Student::visibleTo($actor)
            ->whereNull('user_fk_id')
            ->whereIn('id', $studentIds)
            ->get();

        $created = [];
        $failed = [];

        foreach ($students as $student) {
            try {
                // Authorize for each individual student
                Gate::forUser($actor)->authorize('createAccount', $student);

                DB::transaction(function () use ($actor, $student, &$created) {
                    $student = Student::lockForUpdate()->findOrFail($student->id);
                    if ($student->user_fk_id !== null) {
                        throw new \Exception('Student already has an account.');
                    }

                    $temporaryPassword = Str::password(10, symbols: false);
                    $user = User::create([
                        'user_id' => $student->student_id,
                        'email' => "{$student->student_id}@school.edu",
                        'password' => $temporaryPassword,
                        'status' => 'active',
                        'must_change_password' => true,
                    ]);
                    $user->assignRole(UserRole::Student->value);
                    $student->update(['user_fk_id' => $user->id]);

                    activity()->causedBy($actor)->performedOn($student)
                        ->withProperties([
                            'student_id' => $student->student_id,
                            'user_id' => $user->user_id,
                            'action' => 'account_created_bulk',
                        ])
                        ->log('student account created (bulk)');

                    $created[] = [
                        'student_id' => $student->student_id,
                        'name' => $student->full_name,
                        'user_id' => $user->user_id,
                        'temporary_password' => $temporaryPassword,
                    ];
                });
            } catch (\Throwable $e) {
                $failed[] = ['student_id' => $student->id, 'reason' => $e->getMessage()];
            }
        }

        $foundIds = $students->pluck('id')->toArray();
        $missingIds = array_diff($studentIds, $foundIds);
        foreach ($missingIds as $id) {
            $failed[] = ['student_id' => $id, 'reason' => 'Student not found or not in your assigned departments'];
        }

        return ['created' => $created, 'failed' => $failed];
    }
}