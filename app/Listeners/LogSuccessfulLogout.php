<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        $request = request();
        
        activity('security')
            ->causedBy($event->user)
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId(),
            ])
            ->log('user_logout');
    }
}
