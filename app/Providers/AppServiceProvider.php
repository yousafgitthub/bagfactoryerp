<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\ErpAccessPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        $policy = new ErpAccessPolicy;

        Gate::define('viewDashboard', fn (User $user) => $policy->viewDashboard($user));
        Gate::define('viewModule', fn (User $user, string $module) => $policy->viewModule($user, $module));
        Gate::define('manageInventory', fn (User $user) => $policy->manageInventory($user));
        Gate::define('approvePayroll', fn (User $user) => $policy->approvePayroll($user));
        Gate::define('approveFinancialStatements', fn (User $user) => $policy->approveFinancialStatements($user));
    }
}
