<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Permission-based route examples
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/admin', function () {
        $payload = [
            'message' => 'Welcome to admin area',
            'user' => Auth::user()->name,
            'stats' => [
                'users' => \App\Models\User::query()->count(),
                'roles' => \Spatie\Permission\Models\Role::query()->count(),
                'permissions' => \Spatie\Permission\Models\Permission::query()->count(),
            ],
        ];

        if (app()->environment('testing') || request()->wantsJson()) {
            return response()->json($payload);
        }

        return Inertia::render('admin/Index', [
            'stats' => $payload['stats'],
        ]);
    })->middleware('role:admin')->name('admin.dashboard');

    Route::get('/sales', function () {
        return response()->json([
            'message' => 'Welcome to sales area',
            'user' => Auth::user()->name,
            'permissions' => Auth::user()->getAllPermissions()->pluck('name'),
        ]);
    })->middleware('permission:manage_contacts')->name('sales.dashboard');

    Route::get('/marketing', function () {
        return response()->json([
            'message' => 'Welcome to marketing area',
            'user' => Auth::user()->name,
            'permissions' => Auth::user()->getAllPermissions()->pluck('name'),
        ]);
    })->middleware('permission:create_campaigns')->name('marketing.dashboard');
});

// Protected resource routes with permission middleware
Route::middleware(['auth', 'verified'])->group(function () {
    // Admin user management routes
    Route::middleware('permission:manage_users')->prefix('admin')->group(function () {
        Route::get('/users', [App\Http\Controllers\UserController::class, 'index'])->name('admin.users.index');
        Route::post('/users', [App\Http\Controllers\UserController::class, 'store'])->name('admin.users.store');
        Route::put('/users/{user}', [App\Http\Controllers\UserController::class, 'update'])->name('admin.users.update');
        Route::delete('/users/{user}', [App\Http\Controllers\UserController::class, 'destroy'])->name('admin.users.destroy');
        Route::post('/users/invite', [App\Http\Controllers\UserController::class, 'invite'])->name('admin.users.invite');
        Route::post('/users/{user}/resend-invite', [App\Http\Controllers\UserController::class, 'resendInvite'])->name('admin.users.resend-invite');
    });

    // Contact routes
    Route::resource('contacts', ContactController::class);
    Route::post('/contacts/{id}/restore', [ContactController::class, 'restore'])->name('contacts.restore');
    Route::get('/contacts/check-duplicate', [ContactController::class, 'checkDuplicate'])->name('contacts.check-duplicate');

    // Deal routes
    Route::get('/crm/deals', [DealController::class, 'index'])->name('deals.index');
    Route::get('/crm/deals/new', [DealController::class, 'create'])->name('deals.create');
    Route::post('/crm/deals', [DealController::class, 'store'])->name('deals.store');
    Route::get('/crm/deals/{deal}', [DealController::class, 'show'])->name('deals.show');
    Route::get('/crm/deals/{deal}/edit', [DealController::class, 'edit'])->name('deals.edit');
    Route::put('/crm/deals/{deal}', [DealController::class, 'update'])->name('deals.update');
    Route::delete('/crm/deals/{deal}', [DealController::class, 'destroy'])->name('deals.destroy');
    Route::post('/crm/deals/{id}/restore', [DealController::class, 'restore'])->name('deals.restore');

    // Deal transitions
    Route::post('/crm/deals/{deal}/stage', [DealController::class, 'changeStage'])->name('deals.stage');
    Route::post('/crm/deals/{deal}/win', [DealController::class, 'win'])->name('deals.win');
    Route::post('/crm/deals/{deal}/lose', [DealController::class, 'lose'])->name('deals.lose');

    // Task routes
    Route::resource('tasks', TaskController::class)->except(['create', 'edit']);

    // Product routes - only admins can manage products
    Route::resource('products', ProductController::class)
        ->except(['create', 'edit'])
        ->middleware('permission:manage_products');

    // Audit log routes - admin only
    Route::middleware('permission:view_audit_logs')->prefix('admin')->group(function () {
        Route::get('/audit-logs', [App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('admin.audit-logs.index');
        Route::get('/audit-logs/{activity}', [App\Http\Controllers\Admin\AuditLogController::class, 'show'])->name('admin.audit-logs.show');
    });

    // Role management routes - admin only (moved from settings)
    Route::middleware('permission:manage_roles')->prefix('admin')->group(function () {
        Route::get('/roles', [App\Http\Controllers\Settings\RoleController::class, 'index'])->name('admin.roles.index');
        Route::post('/roles', [App\Http\Controllers\Settings\RoleController::class, 'store'])->name('admin.roles.store');
        Route::put('/roles/{role}', [App\Http\Controllers\Settings\RoleController::class, 'update'])->name('admin.roles.update');
        Route::delete('/roles/{role}', [App\Http\Controllers\Settings\RoleController::class, 'destroy'])->name('admin.roles.destroy');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
