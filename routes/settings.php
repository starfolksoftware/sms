<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');

    // Role management routes for settings
    Route::middleware('permission:manage_roles')->prefix('settings')->group(function () {
        Route::get('/roles', [App\Http\Controllers\Settings\RoleController::class, 'index'])->name('settings.roles.index');
        Route::post('/roles', [App\Http\Controllers\Settings\RoleController::class, 'store'])->name('settings.roles.store');
        Route::put('/roles/{role}', [App\Http\Controllers\Settings\RoleController::class, 'update'])->name('settings.roles.update');
        Route::delete('/roles/{role}', [App\Http\Controllers\Settings\RoleController::class, 'destroy'])->name('settings.roles.destroy');
    });
});
