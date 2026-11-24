<?php

namespace App\Http\Controllers\Registrar;

use App\Actions\Accounts\CreateStudentAccount;
use App\Actions\Accounts\CreateStudentAccounts;
use App\Actions\Accounts\ResetStudentPassword;
use App\Actions\Accounts\SetStudentAccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Registrar\BulkStudentAccountRequest;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class StudentAccountController extends Controller
{
    public function store(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $student = Student::visibleTo($user)->findOrFail($id);

        $action = new CreateStudentAccount();
        $result = $action->handle($user, $student);

        return redirect()->route('registrar.students.index')
            ->with('credentials', [$result]);
    }

    public function bulkStore(BulkStudentAccountRequest $request): RedirectResponse
    {
        $user = $request->user();

        $action = new CreateStudentAccounts();
        $result = $action->handle($user, $request->validated('student_ids'));

        return redirect()->route('registrar.students.index')
            ->with('bulk_credentials', $result['created'])
            ->with('bulk_failed', $result['failed']);
    }

    public function resetPassword(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $student = Student::visibleTo($user)->findOrFail($id);

        $action = new ResetStudentPassword();
        $result = $action->handle($user, $student);

        return redirect()->route('registrar.students.index')
            ->with('credentials', [$result]);
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $student = Student::visibleTo($user)->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $action = new SetStudentAccountStatus();
        $action->handle($user, $student, $validated['status']);

        return redirect()->route('registrar.students.index')
            ->with('success', "Student account {$validated['status']} successfully.");
    }
}