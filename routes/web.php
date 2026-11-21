<?php

use App\Enums\UserRole;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => false,
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
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