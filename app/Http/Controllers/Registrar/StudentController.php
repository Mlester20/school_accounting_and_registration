<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registrar\StoreStudentRequest;
use App\Http\Requests\Registrar\UpdateStudentRequest;
use App\Http\Resources\Registrar\ProgramResource;
use App\Http\Resources\Registrar\StudentResource;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Escape LIKE wildcards: %, _, \
        $escapedSearch = $request->search ? str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $request->search
        ) : null;

        $query = Student::visibleTo($user)
            ->with(['program.department', 'user'])
            ->when($escapedSearch, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('student_id', 'LIKE', "%{$search}%")
                        ->orWhere('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('middle_name', 'LIKE', "%{$search}%");
                });
            })
            ->when($request->program_id, function ($q, $programId) {
                $q->where('program_id', $programId);
            })
            ->when($request->account_status, function ($q, $status) {
                if ($status === 'has_account') {
                    $q->whereNotNull('user_fk_id');
                } elseif ($status === 'no_account') {
                    $q->whereNull('user_fk_id');
                } elseif ($status === 'inactive') {
                    $q->whereHas('user', function ($u) {
                        $u->where('status', 'inactive');
                    });
                }
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id');

        $students = $query->paginate(15)->withQueryString();

        $programs = Program::whereIn('department_id', $user->departmentIds())
            ->orderBy('code')
            ->get();

        // Temporary credentials travel via Inertia's flash bag, not as regular props:
        // flash data is merged into the page object's top-level "flash" key and is never
        // persisted in the browser's history state (Inertia 2 strips it when saving history).
        // The session copy was consumed by pull() above, so it is delivered exactly once.
        if ($credentials = $request->session()->pull('credentials')) {
            Inertia::flash('credentials', $credentials);
        }
        if ($bulkCredentials = $request->session()->pull('bulk_credentials')) {
            Inertia::flash('bulk_credentials', $bulkCredentials);
        }
        if ($bulkFailed = $request->session()->pull('bulk_failed')) {
            Inertia::flash('bulk_failed', $bulkFailed);
        }

        return Inertia::render('Registrar/Students/Index', [
            'students' => StudentResource::collection($students->items())->resolve(),
            'students_pagination' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
                'from' => $students->firstItem(),
                'to' => $students->lastItem(),
                'links' => $students->linkCollection()->toArray(),
            ],
            'programs' => ProgramResource::collection($programs)->resolve(),
            'filters' => $request->only(['search', 'program_id', 'account_status']),
            'can' => [
                'create' => $user->can('students.manage'),
                'createAccount' => $user->can('accounts.student.create'),
                'resetPassword' => $user->can('accounts.student.manage'),
                'setStatus' => $user->can('accounts.student.manage'),
            ],
        ]);
    }

    public function create(): Response
    {
        $user = request()->user();

        $programs = Program::whereIn('department_id', $user->departmentIds())
            ->orderBy('code')
            ->get();

        return Inertia::render('Registrar/Students/Create', [
            'programs' => ProgramResource::collection($programs)->resolve(),
            'can' => [
                'create' => $user->can('students.manage'),
            ],
        ]);
    }

    public function store(StoreStudentRequest $request): \Illuminate\Http\RedirectResponse
    {
        Student::create($request->validated());

        return redirect()->route('registrar.students.index')
            ->with('success', 'Student created successfully.');
    }

    public function show(Request $request, int $id): Response
    {
        $user = $request->user();
        $student = Student::visibleTo($user)
            ->with(['program.department', 'user'])
            ->findOrFail($id);

        // Same one-time flash transport as index(): never in history state.
        if ($credentials = $request->session()->pull('credentials')) {
            Inertia::flash('credentials', $credentials);
        }

        return Inertia::render('Registrar/Students/Show', [
            'student' => StudentResource::make($student)->resolve(),
            'can' => [
                'createAccount' => $user->can('accounts.student.create', $student),
                'resetPassword' => $user->can('accounts.student.manage', $student),
                'setStatus' => $user->can('accounts.student.manage', $student),
            ],
        ]);
    }

    public function edit(Request $request, int $id): Response
    {
        $user = $request->user();
        $student = Student::visibleTo($user)
            ->with(['program.department'])
            ->findOrFail($id);

        $programs = Program::whereIn('department_id', $user->departmentIds())
            ->orderBy('code')
            ->get();

        return Inertia::render('Registrar/Students/Edit', [
            'student' => StudentResource::make($student)->resolve(),
            'programs' => ProgramResource::collection($programs)->resolve(),
            'can' => [
                'update' => $user->can('students.manage', $student),
            ],
        ]);
    }

    public function update(UpdateStudentRequest $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $student = Student::visibleTo($user)->findOrFail($id);

        $student->update($request->validated());

        return redirect()->route('registrar.students.index')
            ->with('success', 'Student updated successfully.');
    }
}