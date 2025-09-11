<?php

use Illuminate\Support\Facades\Route;
Route::get('/', function () { return response('OK', 200); });

Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login']);
Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->middleware('auth');

Route::middleware(['web', 'auth', 'permission:view_dashboard'])->group(function () {
	Route::get('/dashboard', function () {
		return 'Dashboard placeholder';
	});
});

Route::middleware(['web', 'auth', 'permission:view_audit_logs'])
	->get('/admin/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index']);

// Route::get('/', function () {
//     return view('welcome');
// });
