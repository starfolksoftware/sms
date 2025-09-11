<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->can('view_audit_logs'), 403);

        $logs = Activity::query()
            ->latest('id')
            ->paginate(50)
            ->through(function (Activity $a) {
                return [
                    'id' => $a->id,
                    'time' => $a->created_at?->toIso8601String(),
                    'log' => $a->log_name,
                    'description' => $a->description,
                    'event' => $a->event,
                    'causer_id' => $a->causer_id,
                    'causer_type' => $a->causer_type,
                    'subject_id' => $a->subject_id,
                    'subject_type' => $a->subject_type,
                    'properties' => collect($a->properties)->take(1),
                ];
            });

        if ($request->wantsJson()) {
            return response()->json($logs);
        }

        return response()->view('audit.logs', ['logs' => $logs]);
    }
}
