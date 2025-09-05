<?php

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
        return response()->json([
            'message' => 'Welcome to admin area',
            'user' => auth()->user()->name,
            'permissions' => auth()->user()->getAllPermissions()->pluck('name'),
        ]);
    })->middleware('permission:manage_users')->name('admin.dashboard');

    Route::get('/sales', function () {
        return response()->json([
            'message' => 'Welcome to sales area',
            'user' => auth()->user()->name,
            'permissions' => auth()->user()->getAllPermissions()->pluck('name'),
        ]);
    })->middleware('permission:manage_clients')->name('sales.dashboard');

    Route::get('/marketing', function () {
        return response()->json([
            'message' => 'Welcome to marketing area',
            'user' => auth()->user()->name,
            'permissions' => auth()->user()->getAllPermissions()->pluck('name'),
        ]);
    })->middleware('permission:create_campaigns')->name('marketing.dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
