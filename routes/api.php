<?php

use App\Http\Controllers\Api\ContactTimelineController;
use App\Http\Controllers\Api\DealController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web'])->prefix('deals/{deal}')->group(function () {
    Route::post('/win', [DealController::class, 'win'])->name('api.deals.win');
    Route::post('/lose', [DealController::class, 'lose'])->name('api.deals.lose');
});

Route::middleware(['auth:web'])->prefix('contacts/{contact}')->group(function () {
    Route::get('/timeline', [ContactTimelineController::class, 'index'])->name('api.contacts.timeline');
});
