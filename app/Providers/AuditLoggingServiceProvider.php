<?php

namespace App\Providers;

use App\Events\DataExported;
use App\Listeners\LogDataExported;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

class AuditLoggingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(Dispatcher $events): void
    {
        // Auth events
        $events->listen(Login::class, function (Login $event): void {
            activity('security')
                ->causedBy($event->user)
                ->event('login')
                ->withProperties($this->requestProperties())
                ->log('user.login.success');
        });

        $events->listen(Logout::class, function (Logout $event): void {
            activity('security')
                ->causedBy($event->user)
                ->event('logout')
                ->withProperties($this->requestProperties())
                ->log('user.logout');
        });

        $events->listen(Failed::class, function (Failed $event): void {
            activity('security')
                ->event('login_failed')
                ->withProperties(array_merge($this->requestProperties(), [
                    'email' => $event->credentials['email'] ?? null,
                    'guard' => $event->guard,
                ]))
                ->log('user.login.failed');
        });

        // Data export event
        $events->listen(DataExported::class, LogDataExported::class);

        // Schedule pruning
        $this->app->booted(function (): void {
            Schedule::call(function (): void {
                $days = (int) config('activitylog.delete_records_older_than_days');
                Activity::query()
                    ->where('created_at', '<', now()->subDays($days))
                    ->chunkById(100, function ($activities) {
                        $activities->each->delete();
                    });
            })->daily()->name('prune:activity-log')->onOneServer();
        });
    }

    protected function requestProperties(): array
    {
        $request = request();
        return [
            'ip' => $request?->ip(),
            'user_agent' => substr($request?->userAgent() ?? '', 0, 500),
            'request_id' => $request?->headers->get('X-Request-Id'),
            'session_id' => $request?->session()->getId(),
        ];
    }
}
