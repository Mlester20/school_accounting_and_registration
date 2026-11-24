<?php

namespace App\Actions\Accounts;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ResetStudentPassword
{
    public function handle(User $actor, Student $student): array
    {
        Gate::forUser($actor)->authorize('resetPassword', $student);

        return DB::transaction(function () use ($actor, $student) {
            $student = Student::lockForUpdate()->findOrFail($student->id);
            if ($student->user_fk_id === null) {
                abort(422, 'Student has no account to reset.');
            }

            $user = User::findOrFail($student->user_fk_id);
            $temporaryPassword = Str::password(10, symbols: false);

            $user->update([
                'password' => $temporaryPassword,
                'must_change_password' => true,
                'status' => 'active',
            ]);

            DB::table('sessions')->where('user_id', $user->id)->delete();

            activity()->causedBy($actor)->performedOn($student)
                ->withProperties([
                    'student_id' => $student->student_id,
                    'user_id' => $user->user_id,
                    'action' => 'password_reset',
                    'must_change_password' => true,
                ])
                ->log('student_password_reset');

            return [
                'student_id' => $student->student_id,
                'user_id' => $user->user_id,
                'temporary_password' => $temporaryPassword,
            ];
        });
    }
}