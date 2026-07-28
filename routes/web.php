<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StaffUserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])->name('register.store');
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::middleware(['auth', 'active.staff'])->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Staff user management
    Route::middleware('permission:staff-users.view')->group(function (): void {
        Route::get('staff', [StaffUserController::class, 'index'])->name('staff.index');
        Route::get('staff/create', [StaffUserController::class, 'create'])->name('staff.create')->middleware('permission:staff-users.create');
        Route::post('staff', [StaffUserController::class, 'store'])->name('staff.store')->middleware('permission:staff-users.create');
        Route::get('staff/{user}/edit', [StaffUserController::class, 'edit'])->name('staff.edit')->middleware('permission:staff-users.update');
        Route::put('staff/{user}', [StaffUserController::class, 'update'])->name('staff.update')->middleware('permission:staff-users.update');
        Route::delete('staff/{user}', [StaffUserController::class, 'destroy'])->name('staff.destroy')->middleware('permission:staff-users.manage');
    });

    // Role & permission management
    Route::middleware('permission:roles.view')->group(function (): void {
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create')->middleware('permission:roles.manage');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store')->middleware('permission:roles.manage');
        Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit')->middleware('permission:roles.manage');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update')->middleware('permission:roles.manage');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy')->middleware('permission:roles.manage');
    });
});
