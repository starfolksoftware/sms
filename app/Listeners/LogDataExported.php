<?php

namespace App\Listeners;

use App\Events\DataExported;

class LogDataExported
{
    public function handle(DataExported $event): void
    {
    /** @var \App\Models\User|null $user */
    $user = request()?->user();
        activity('data_ops')
            ->event('export')
            ->when($user, fn ($log) => $log->causedBy($user))
            ->withProperties([
                'module' => $event->module,
                'filters' => $this->sanitizeFilters($event->filters),
                'count' => $event->count,
                'format' => $event->format,
                'path' => $event->path,
                'export_id' => $event->exportId,
            ])
            ->log('data.export.performed');
    }

    protected function sanitizeFilters(array $filters): array
    {
        // Redact common sensitive keys
        $sensitive = ['email', 'phone', 'token', 'password'];
        return collect($filters)->map(function ($value, $key) use ($sensitive) {
            if (in_array(strtolower($key), $sensitive, true)) {
                return 'REDACTED';
            }
            return $value;
        })->all();
    }
}
