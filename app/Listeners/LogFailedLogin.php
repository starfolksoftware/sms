<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Spatie\Activitylog\Facades\LogActivity;

class LogFailedLogin
{
    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        $request = request();
        
        LogActivity::useLog('security')
            ->causedBy($event->user) // May be null for failed attempts
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId(),
                'email' => $event->credentials['email'] ?? null,
                'guard' => $event->guard,
            ])
            ->log('login_failed');
    }
}
