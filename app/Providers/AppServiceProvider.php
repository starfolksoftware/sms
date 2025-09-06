<?php

namespace App\Providers;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Product;
use App\Models\Task;
use App\Policies\ContactPolicy;
use App\Policies\DealPolicy;
use App\Policies\ProductPolicy;
use App\Policies\TaskPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Contact::class => ContactPolicy::class,
        Deal::class => DealPolicy::class,
        Task::class => TaskPolicy::class,
        Product::class => ProductPolicy::class,
    ];

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
        $this->registerPolicies();
    }

    /**
     * Register the application's policies.
     */
    public function registerPolicies(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }
}
