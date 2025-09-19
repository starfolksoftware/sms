<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\Contact::observe(\App\Observers\ContactObserver::class);
        \App\Models\Deal::observe(\App\Observers\DealObserver::class);

        // Register event listeners
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\DealCreated::class,
            \App\Listeners\SendDealCreatedNotifications::class,
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\DealStageChanged::class,
            \App\Listeners\SendDealStageChangedNotifications::class,
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\DealWon::class,
            \App\Listeners\SendDealWonNotifications::class,
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\DealLost::class,
            \App\Listeners\SendDealLostNotifications::class,
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\DealAssigned::class,
            \App\Listeners\SendDealAssignedNotifications::class,
        );
    }
}
