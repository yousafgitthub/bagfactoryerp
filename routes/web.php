<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    $moduleRoutes = [
        'inventory' => 'erp.inventory',
        'dana-production' => 'erp.dana-production',
        'shopping-bag-production' => 'erp.shopping-bag-production',
        'machines-halls' => 'erp.machines-halls',
        'employees' => 'erp.employees',
        'attendance' => 'erp.attendance',
        'payroll' => 'erp.payroll',
        'purchases' => 'erp.purchases',
        'suppliers' => 'erp.suppliers',
        'sales' => 'erp.sales',
        'customers' => 'erp.customers',
        'expenses' => 'erp.expenses',
        'accounting' => 'erp.accounting',
        'reports' => 'erp.reports',
        'settings' => 'erp.settings',
    ];

    foreach ($moduleRoutes as $slug => $routeName) {
        Route::get("/{$slug}", function (\Illuminate\Http\Request $request) use ($slug) {
            return app(ModuleController::class)($request, $slug);
        })
            ->middleware("permission:{$slug}.view")
            ->name($routeName);
    }
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';