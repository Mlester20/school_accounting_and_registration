<?php

namespace App\Actions\Accounts;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SetStudentAccountStatus
{
    public function handle(User $actor, Student $student, string $status): User
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            abort(422, 'Invalid status.');
        }

        Gate::forUser($actor)->authorize('setStatus', $student);

        return DB::transaction(function () use ($actor, $student, $status) {
            $student = Student::lockForUpdate()->findOrFail($student->id);
            if ($student->user_fk_id === null) {
                abort(422, 'Student has no account.');
            }

            $user = User::findOrFail($student->user_fk_id);
            $oldStatus = $user->status;
            $user->update(['status' => $status]);

            if ($status === 'inactive') {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            activity()->causedBy($actor)->performedOn($student)
                ->withProperties([
                    'student_id' => $student->student_id,
                    'user_id' => $user->user_id,
                    'old_status' => $oldStatus,
                    'new_status' => $status,
                    'sessions_invalidated' => $status === 'inactive',
                ])
                ->log('student_account_status_changed');

            return $user;
        });
    }
}