<?php

use App\Http\Controllers\Api\DealController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('deals')->group(function () {
    // Standard resource routes
    Route::get('/', [DealController::class, 'index'])->name('api.deals.index');
    Route::post('/', [DealController::class, 'store'])->name('api.deals.store');
    Route::get('/{deal}', [DealController::class, 'show'])->name('api.deals.show');
    Route::put('/{deal}', [DealController::class, 'update'])->name('api.deals.update');
    Route::delete('/{deal}', [DealController::class, 'destroy'])->name('api.deals.destroy');

    // Deal transition routes
    Route::post('/{deal}/restore', [DealController::class, 'restore'])
        ->withTrashed()
        ->name('api.deals.restore');
    Route::post('/{deal}/stage', [DealController::class, 'changeStage'])->name('api.deals.stage');
    Route::post('/{deal}/win', [DealController::class, 'win'])->name('api.deals.win');
    Route::post('/{deal}/lose', [DealController::class, 'lose'])->name('api.deals.lose');
});

// Note: Contact routes remain in web.php to avoid route conflicts with authorization tests
