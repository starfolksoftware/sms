<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'permission:view_audit_logs'])
	->get('/admin/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index']);

// Route::get('/', function () {
//     return view('welcome');
// });
