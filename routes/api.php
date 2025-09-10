<?php

use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Contact routes
    Route::resource('contacts', ContactController::class)->except(['create', 'edit']);
    Route::post('contacts/{contact}/restore', [ContactController::class, 'restore'])->name('contacts.restore');
});
