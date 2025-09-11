<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Contact::class => \App\Policies\ContactPolicy::class,
        \App\Models\Deal::class => \App\Policies\DealPolicy::class,
        \App\Models\Task::class => \App\Policies\TaskPolicy::class,
        \App\Models\Product::class => \App\Policies\ProductPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('viewReports', function ($user): bool {
            return $user->hasRole('Admin');
        });
    }
}
