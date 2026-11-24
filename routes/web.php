<?php

use App\Enums\UserRole;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Registrar\StudentController;
use App\Http\Controllers\Registrar\StudentAccountController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Root URL: guests go to the login page, authenticated users to their
// role dashboard (via the /dashboard redirect below).
Route::get('/', function () {
    $user = auth()->user();

    return $user
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

/**
 * Dashboard redirect based on role priority.
 * Priority: admin > registrar > accounting > faculty > student
 */
Route::get('/dashboard', function () {
    $user = auth()->user();

    if (! $user) {
        return redirect()->route('login');
    }

    // Check roles in priority order
    if ($user->hasRole(UserRole::Admin->value)) {
        return redirect()->route('admin.dashboard');
    }
    if ($user->hasRole(UserRole::Registrar->value)) {
        return redirect()->route('registrar.dashboard');
    }
    if ($user->hasRole(UserRole::Accounting->value)) {
        return redirect()->route('accounting.dashboard');
    }
    if ($user->hasRole(UserRole::Faculty->value)) {
        return redirect()->route('faculty.dashboard');
    }
    if ($user->hasRole(UserRole::Student->value)) {
        return redirect()->route('student.dashboard');
    }

    // No role assigned
    abort(403, 'No role assigned.');
})->middleware(['auth', 'password.changed', 'active'])->name('dashboard');

// Admin area
Route::middleware(['auth', 'password.changed', 'active', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');
});

// Registrar area
Route::middleware(['auth', 'password.changed', 'active', 'role:registrar'])->prefix('registrar')->name('registrar.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Registrar/Dashboard');
    })->name('dashboard');

    Route::resource('students', StudentController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update'])
        ->parameters(['students' => 'student']);

    Route::post('students/{student}/account', [StudentAccountController::class, 'store'])
        ->name('students.account.store')
        ->whereNumber('student');
    Route::post('students/accounts/bulk', [StudentAccountController::class, 'bulkStore'])
        ->name('students.accounts.bulk');
    Route::post('students/{student}/account/reset-password', [StudentAccountController::class, 'resetPassword'])
        ->name('students.account.reset-password')
        ->whereNumber('student');
    Route::put('students/{student}/account/status', [StudentAccountController::class, 'updateStatus'])
        ->name('students.account.status')
        ->whereNumber('student');
});

// Accounting area
Route::middleware(['auth', 'password.changed', 'active', 'role:accounting'])->prefix('accounting')->name('accounting.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Accounting/Dashboard');
    })->name('dashboard');
});

// Faculty area
Route::middleware(['auth', 'password.changed', 'active', 'role:faculty'])->prefix('faculty')->name('faculty.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Faculty/Dashboard');
    })->name('dashboard');
});

// Student area
Route::middleware(['auth', 'password.changed', 'active', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Student/Dashboard');
    })->name('dashboard');
});

Route::middleware(['auth', 'password.changed', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/password', [\App\Http\Controllers\Auth\PasswordController::class, 'update'])->name('password.update');
});

require __DIR__.'/auth.php';