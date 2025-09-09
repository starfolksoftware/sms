<?php

namespace App\Listeners;

use App\Events\DataExported;
use Spatie\Activitylog\Facades\LogActivity;

class LogDataExport
{
    /**
     * Handle the event.
     */
    public function handle(DataExported $event): void
    {
        $request = request();
        
        LogActivity::useLog('data_ops')
            ->causedBy(auth()->user())
            ->withProperties([
                'ip' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'module' => $event->module,
                'filters' => $event->filters,
                'record_count' => $event->recordCount,
                'format' => $event->format,
                'export_path' => $event->exportPath,
            ])
            ->log('data_exported');
    }
}
