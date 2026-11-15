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
        Gate::define('manageDanaProduction', fn (User $user) => $policy->manageDanaProduction($user));
        Gate::define('manageBagProduction', fn (User $user) => $policy->manageBagProduction($user));
        Gate::define('manageEmployees', fn (User $user) => $policy->manageEmployees($user));
        Gate::define('manageAttendance', fn (User $user) => $policy->manageAttendance($user));
        Gate::define('approveOvertime', fn (User $user) => $policy->approveOvertime($user));
        Gate::define('managePayroll', fn (User $user) => $policy->managePayroll($user));
        Gate::define('approvePayroll', fn (User $user) => $policy->approvePayroll($user));
        Gate::define('approveFinancialStatements', fn (User $user) => $policy->approveFinancialStatements($user));
        Gate::define('managePurchases', fn (User $user) => $policy->managePurchases($user));
        Gate::define('manageSuppliers', fn (User $user) => $policy->manageSuppliers($user));
        Gate::define('manageSales', fn (User $user) => $policy->manageSales($user));
        Gate::define('manageCustomers', fn (User $user) => $policy->manageCustomers($user));
        Gate::define('manageExpenses', fn (User $user) => $policy->manageExpenses($user));
        Gate::define('manageAccounting', fn (User $user) => $policy->manageAccounting($user));
    }
}
