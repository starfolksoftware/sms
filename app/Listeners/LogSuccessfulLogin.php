<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Spatie\Activitylog\Facades\LogActivity;

class LogSuccessfulLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $request = request();
        
        LogActivity::useLog('security')
            ->causedBy($event->user)
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId(),
                'remember' => $event->remember,
            ])
            ->log('user_login');
            
        // Update last_login_at timestamp
        $event->user->update(['last_login_at' => now()]);
    }
}
