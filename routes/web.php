<?php

use Illuminate\Support\Facades\Route;
Route::get('/', function () { return response('OK', 200); });

Route::middleware(['web', 'auth', 'permission:view_dashboard'])->group(function () {
	Route::get('/dashboard', function () {
		return 'Dashboard placeholder';
	});
});

// Route::get('/', function () {
//     return view('welcome');
// });
