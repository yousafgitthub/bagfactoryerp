<?php

use App\Http\Controllers\Dana\DanaBatchController;
use App\Http\Controllers\Dana\DanaCostingReportController;
use App\Http\Controllers\Dana\DanaHubController;
use App\Http\Controllers\Dana\DanaRecipeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Inventory\InventoryBalanceController;
use App\Http\Controllers\Inventory\InventoryHubController;
use App\Http\Controllers\Inventory\InventoryLedgerController;
use App\Http\Controllers\Inventory\InventoryReconciliationController;
use App\Http\Controllers\Inventory\InventoryTransactionController;
use App\Http\Controllers\Inventory\MaterialController;
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

    Route::middleware('permission:inventory.view')->prefix('inventory')->group(function () {
        Route::get('/', InventoryHubController::class)->name('erp.inventory');

        Route::get('/materials', [MaterialController::class, 'index'])->name('inventory.materials.index');
        Route::get('/materials/create', [MaterialController::class, 'create'])
            ->middleware('permission:inventory.manage')
            ->name('inventory.materials.create');
        Route::post('/materials', [MaterialController::class, 'store'])
            ->middleware('permission:inventory.manage')
            ->name('inventory.materials.store');
        Route::get('/materials/{material}', [MaterialController::class, 'show'])->name('inventory.materials.show');
        Route::get('/materials/{material}/edit', [MaterialController::class, 'edit'])
            ->middleware('permission:inventory.manage')
            ->name('inventory.materials.edit');
        Route::put('/materials/{material}', [MaterialController::class, 'update'])
            ->middleware('permission:inventory.manage')
            ->name('inventory.materials.update');
        Route::post('/materials/{material}/variants', [MaterialController::class, 'storeVariant'])
            ->middleware('permission:inventory.manage')
            ->name('inventory.materials.variants.store');
        Route::post('/materials/{material}/deactivate', [MaterialController::class, 'deactivate'])
            ->middleware('permission:inventory.manage')
            ->name('inventory.materials.deactivate');

        Route::get('/stock', [InventoryBalanceController::class, 'index'])->name('inventory.balances.index');
        Route::get('/ledger', [InventoryLedgerController::class, 'index'])->name('inventory.ledger.index');
        Route::get('/reconciliation', InventoryReconciliationController::class)->name('inventory.reconciliation');

        Route::get('/transactions', [InventoryTransactionController::class, 'index'])->name('inventory.transactions.index');
        Route::get('/transactions/create', [InventoryTransactionController::class, 'create'])
            ->middleware('permission:inventory.manage')
            ->name('inventory.transactions.create');
        Route::post('/transactions', [InventoryTransactionController::class, 'store'])
            ->middleware('permission:inventory.manage')
            ->name('inventory.transactions.store');
        Route::get('/transactions/{transaction}', [InventoryTransactionController::class, 'show'])->name('inventory.transactions.show');
        Route::post('/transactions/{transaction}/post', [InventoryTransactionController::class, 'post'])
            ->middleware('permission:inventory.manage')
            ->name('inventory.transactions.post');
        Route::post('/transactions/{transaction}/reverse', [InventoryTransactionController::class, 'reverse'])
            ->middleware('permission:inventory.manage')
            ->name('inventory.transactions.reverse');
    });

    Route::middleware('permission:dana-production.view')->prefix('dana-production')->group(function () {
        Route::get('/', DanaHubController::class)->name('erp.dana-production');

        Route::get('/batches', [DanaBatchController::class, 'index'])->name('dana.batches.index');
        Route::get('/batches/create', [DanaBatchController::class, 'create'])
            ->middleware('permission:dana-production.manage')
            ->name('dana.batches.create');
        Route::post('/batches', [DanaBatchController::class, 'store'])
            ->middleware('permission:dana-production.manage')
            ->name('dana.batches.store');
        Route::get('/batches/{batch}', [DanaBatchController::class, 'show'])->name('dana.batches.show');
        Route::get('/batches/{batch}/edit', [DanaBatchController::class, 'edit'])
            ->middleware('permission:dana-production.manage')
            ->name('dana.batches.edit');
        Route::put('/batches/{batch}', [DanaBatchController::class, 'update'])
            ->middleware('permission:dana-production.manage')
            ->name('dana.batches.update');
        Route::post('/batches/{batch}/workflow/{action}', [DanaBatchController::class, 'workflow'])
            ->middleware('permission:dana-production.manage')
            ->name('dana.batches.workflow');
        Route::post('/batches/{batch}/post', [DanaBatchController::class, 'post'])
            ->middleware('permission:dana-production.manage')
            ->name('dana.batches.post');
        Route::post('/batches/{batch}/reverse', [DanaBatchController::class, 'reverse'])
            ->middleware('permission:dana-production.manage')
            ->name('dana.batches.reverse');
        Route::get('/batches/{batch}/print', [DanaBatchController::class, 'print'])->name('dana.batches.print');

        Route::get('/recipes', [DanaRecipeController::class, 'index'])->name('dana.recipes.index');
        Route::get('/recipes/create', [DanaRecipeController::class, 'create'])
            ->middleware('permission:dana-production.manage')
            ->name('dana.recipes.create');
        Route::post('/recipes', [DanaRecipeController::class, 'store'])
            ->middleware('permission:dana-production.manage')
            ->name('dana.recipes.store');
        Route::get('/recipes/{recipe}/edit', [DanaRecipeController::class, 'edit'])
            ->middleware('permission:dana-production.manage')
            ->name('dana.recipes.edit');
        Route::put('/recipes/{recipe}', [DanaRecipeController::class, 'update'])
            ->middleware('permission:dana-production.manage')
            ->name('dana.recipes.update');

        Route::get('/reports/costing', DanaCostingReportController::class)->name('dana.reports.costing');
    });

    $moduleRoutes = [
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