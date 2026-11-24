<?php

namespace App\Actions\Accounts;

use App\Models\Student;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CreateStudentAccount
{
    public function handle(User $actor, Student $student): array
    {
        Gate::forUser($actor)->authorize('createAccount', $student);

        return DB::transaction(function () use ($actor, $student) {
            $student = Student::lockForUpdate()->findOrFail($student->id);

            if ($student->user_fk_id !== null) {
                abort(422, 'Student already has an account.');
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

            activity()
                ->causedBy($actor)
                ->performedOn($student)
                ->withProperties([
                    'student_id' => $student->student_id,
                    'user_id' => $user->user_id,
                    'action' => 'account_created',
                ])
                ->log('student_account_created');

            return [
                'student_id' => $student->student_id,
                'user_id' => $user->user_id,
                'temporary_password' => $temporaryPassword,
            ];
        });
    }
}