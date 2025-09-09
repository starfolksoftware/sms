<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs.
     */
    public function index(Request $request)
    {
        $this->checkAuthorization('view_audit_logs');

        $logs = Activity::with(['causer', 'subject'])
            ->when($request->log_name, fn($query, $logName) => 
                $query->where('log_name', $logName)
            )
            ->when($request->event, fn($query, $event) => 
                $query->where('event', $event)
            )
            ->when($request->causer_type && $request->causer_id, fn($query) => 
                $query->where('causer_type', $request->causer_type)
                      ->where('causer_id', $request->causer_id)
            )
            ->latest()
            ->paginate(50);

        if ($request->expectsJson()) {
            return response()->json($logs);
        }

        return response()->json([
            'message' => 'Audit logs retrieved successfully',
            'data' => $logs,
            'filters' => [
                'log_names' => Activity::distinct()->pluck('log_name')->filter(),
                'events' => Activity::distinct()->pluck('event')->filter(),
            ]
        ]);
    }

    /**
     * Show the specified audit log entry.
     */
    public function show(Request $request, Activity $activity)
    {
        $this->checkAuthorization('view_audit_logs');

        $activity->load(['causer', 'subject']);

        return response()->json([
            'message' => 'Audit log entry retrieved successfully',
            'data' => $activity,
        ]);
    }

    /**
     * Check user authorization for audit log access.
     */
    protected function checkAuthorization(string $permission): void
    {
        if (!auth()->user()->can($permission)) {
            abort(403, 'Unauthorized access to audit logs.');
        }
    }
}
