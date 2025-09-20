<?php

use App\Http\Controllers\Api\ContactTimelineController;
use App\Http\Controllers\Api\DealController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web'])->prefix('deals/{deal}')->group(function () {
    Route::post('/win', [DealController::class, 'win'])->name('api.deals.win');
    Route::post('/lose', [DealController::class, 'lose'])->name('api.deals.lose');
});

Route::middleware(['auth:web'])->prefix('contacts/{contact}')->group(function () {
    Route::get('/timeline', [ContactTimelineController::class, 'index'])->name('api.contacts.timeline');
});

// Webhook endpoints (no auth required, handled in controller)
Route::prefix('webhooks')->group(function () {
    Route::post('/lead-form', [WebhookController::class, 'leadForm'])
        ->name('webhooks.lead-form')
        ->middleware(['throttle:webhook-rate-limit']);
});
