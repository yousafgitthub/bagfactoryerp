<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MachinesHallsHubController;
use App\Http\Controllers\MasterData\MasterDataController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsHubController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::get('/machines-halls', MachinesHallsHubController::class)
        ->middleware('permission:machines-halls.view')
        ->name('erp.machines-halls');

    Route::get('/settings', SettingsHubController::class)
        ->middleware('permission:settings.view')
        ->name('erp.settings');

    Route::prefix('master-data/{resource}')
        ->where(['resource' => '[a-z0-9\-]+'])
        ->group(function () {
            Route::get('/', [MasterDataController::class, 'index'])->name('master-data.index');
            Route::get('/create', [MasterDataController::class, 'create'])->name('master-data.create');
            Route::post('/', [MasterDataController::class, 'store'])->name('master-data.store');
            Route::get('/{record}/edit', [MasterDataController::class, 'edit'])->name('master-data.edit');
            Route::put('/{record}', [MasterDataController::class, 'update'])->name('master-data.update');
            Route::post('/{record}/deactivate', [MasterDataController::class, 'deactivate'])->name('master-data.deactivate');
        });

    $moduleRoutes = [
        'inventory' => 'erp.inventory',
        'dana-production' => 'erp.dana-production',
        'shopping-bag-production' => 'erp.shopping-bag-production',
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