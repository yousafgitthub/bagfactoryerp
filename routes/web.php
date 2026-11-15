<?php

use App\Http\Controllers\Bag\BagBatchController;
use App\Http\Controllers\Bag\BagBomController;
use App\Http\Controllers\Bag\BagHubController;
use App\Http\Controllers\Bag\BagOrderController;
use App\Http\Controllers\Bag\BagProductController;
use App\Http\Controllers\Bag\BagReportController;
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
use App\Http\Controllers\Hr\AttendanceController;
use App\Http\Controllers\Hr\AttendanceHubController;
use App\Http\Controllers\Hr\EmployeeController;
use App\Http\Controllers\Hr\EmployeeHubController;
use App\Http\Controllers\Hr\HrReportController;
use App\Http\Controllers\Hr\OvertimeController;
use App\Http\Controllers\Hr\EmployeeAdvanceController;
use App\Http\Controllers\Hr\EmployeeLoanController;
use App\Http\Controllers\Hr\PayrollHubController;
use App\Http\Controllers\Hr\PayrollRecordController;
use App\Http\Controllers\Hr\PayrollReportController;
use App\Http\Controllers\Hr\PayrollRunController;
use App\Http\Controllers\Hr\ProductionAssignmentController;
use App\Http\Controllers\MachinesHallsHubController;
use App\Http\Controllers\MasterData\MasterDataController;
use App\Http\Controllers\Accounting\AccountingHubController;
use App\Http\Controllers\Accounting\CompanyLoanController;
use App\Http\Controllers\Accounting\FinancialReportController;
use App\Http\Controllers\Accounting\GlAccountController;
use App\Http\Controllers\Accounting\JournalEntryController;
use App\Http\Controllers\Accounting\TreasuryAccountController;
use App\Http\Controllers\Expenses\ExpenseController;
use App\Http\Controllers\Expenses\ExpensesHubController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Purchases\GoodsReceiptController;
use App\Http\Controllers\Purchases\PurchaseHubController;
use App\Http\Controllers\Purchases\PurchaseInvoiceController;
use App\Http\Controllers\Purchases\PurchaseOrderController;
use App\Http\Controllers\Purchases\PurchaseReportController;
use App\Http\Controllers\Purchases\PurchaseReturnController;
use App\Http\Controllers\Purchases\SupplierController;
use App\Http\Controllers\Purchases\SupplierHubController;
use App\Http\Controllers\Purchases\SupplierPaymentController;
use App\Http\Controllers\Sales\CustomerController;
use App\Http\Controllers\Sales\CustomerHubController;
use App\Http\Controllers\Sales\CustomerReceiptController;
use App\Http\Controllers\Sales\SalesHubController;
use App\Http\Controllers\Sales\SalesInvoiceController;
use App\Http\Controllers\Sales\SalesOrderController;
use App\Http\Controllers\Sales\SalesReportController;
use App\Http\Controllers\Sales\SalesReturnController;
use App\Http\Controllers\Reports\ErpReportController;
use App\Http\Controllers\Reports\ReportsHubController;
use App\Http\Controllers\SettingsHubController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/website.php';

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

    Route::middleware('permission:shopping-bag-production.view')->prefix('shopping-bag-production')->group(function () {
        Route::get('/', BagHubController::class)->name('erp.shopping-bag-production');

        Route::get('/products', [BagProductController::class, 'index'])->name('bag.products.index');
        Route::get('/products/create', [BagProductController::class, 'create'])
            ->middleware('permission:shopping-bag-production.manage')
            ->name('bag.products.create');
        Route::post('/products', [BagProductController::class, 'store'])
            ->middleware('permission:shopping-bag-production.manage')
            ->name('bag.products.store');
        Route::get('/products/{product}/edit', [BagProductController::class, 'edit'])
            ->middleware('permission:shopping-bag-production.manage')
            ->name('bag.products.edit');
        Route::put('/products/{product}', [BagProductController::class, 'update'])
            ->middleware('permission:shopping-bag-production.manage')
            ->name('bag.products.update');

        Route::get('/bom', [BagBomController::class, 'index'])->name('bag.bom.index');
        Route::get('/bom/create', [BagBomController::class, 'create'])
            ->middleware('permission:shopping-bag-production.manage')
            ->name('bag.bom.create');
        Route::post('/bom', [BagBomController::class, 'store'])
            ->middleware('permission:shopping-bag-production.manage')
            ->name('bag.bom.store');
        Route::get('/bom/{bom}/edit', [BagBomController::class, 'edit'])
            ->middleware('permission:shopping-bag-production.manage')
            ->name('bag.bom.edit');
        Route::put('/bom/{bom}', [BagBomController::class, 'update'])
            ->middleware('permission:shopping-bag-production.manage')
            ->name('bag.bom.update');
        Route::post('/bom/{bom}/new-version', [BagBomController::class, 'newVersion'])
            ->middleware('permission:shopping-bag-production.manage')
            ->name('bag.bom.new-version');

        Route::get('/orders', [BagOrderController::class, 'index'])->name('bag.orders.index');
        Route::get('/orders/create', [BagOrderController::class, 'create'])
            ->middleware('permission:shopping-bag-production.manage')
            ->name('bag.orders.create');
        Route::post('/orders', [BagOrderController::class, 'store'])
            ->middleware('permission:shopping-bag-production.manage')
            ->name('bag.orders.store');
        Route::get('/orders/{order}', [BagOrderController::class, 'show'])->name('bag.orders.show');
        Route::get('/orders/{order}/edit', [BagOrderController::class, 'edit'])
            ->middleware('permission:shopping-bag-production.manage')
            ->name('bag.orders.edit');
        Route::put('/orders/{order}', [BagOrderController::class, 'update'])
            ->middleware('permission:shopping-bag-production.manage')
            ->name('bag.orders.update');
        Route::post('/orders/{order}/workflow/{action}', [BagOrderController::class, 'workflow'])
            ->middleware('permission:shopping-bag-production.manage')
            ->name('bag.orders.workflow');

        Route::get('/batches', [BagBatchController::class, 'index'])->name('bag.batches.index');
        Route::get('/batches/create', [BagBatchController::class, 'create'])
            ->middleware('permission:shopping-bag-production.manage')
            ->name('bag.batches.create');
        Route::post('/batches', [BagBatchController::class, 'store'])
            ->middleware('permission:shopping-bag-production.manage')
            ->name('bag.batches.store');
        Route::get('/batches/{batch}', [BagBatchController::class, 'show'])->name('bag.batches.show');
        Route::get('/batches/{batch}/edit', [BagBatchController::class, 'edit'])
            ->middleware('permission:shopping-bag-production.manage')
            ->name('bag.batches.edit');
        Route::put('/batches/{batch}', [BagBatchController::class, 'update'])
            ->middleware('permission:shopping-bag-production.manage')
            ->name('bag.batches.update');
        Route::post('/batches/{batch}/workflow/{action}', [BagBatchController::class, 'workflow'])
            ->middleware('permission:shopping-bag-production.manage')
            ->name('bag.batches.workflow');
        Route::post('/batches/{batch}/post', [BagBatchController::class, 'post'])
            ->middleware('permission:shopping-bag-production.manage')
            ->name('bag.batches.post');
        Route::post('/batches/{batch}/reverse', [BagBatchController::class, 'reverse'])
            ->middleware('permission:shopping-bag-production.manage')
            ->name('bag.batches.reverse');

        Route::get('/reports/costing', [BagReportController::class, 'costing'])->name('bag.reports.costing');
        Route::get('/reports/planning', [BagReportController::class, 'planning'])->name('bag.reports.planning');
        Route::get('/reports/machine-output', [BagReportController::class, 'machineOutput'])->name('bag.reports.machine');
        Route::get('/reports/finished-stock', [BagReportController::class, 'finishedStock'])->name('bag.reports.finished-stock');
    });

    Route::middleware('permission:employees.view')->prefix('employees')->group(function () {
        Route::get('/', EmployeeHubController::class)->name('erp.employees');

        Route::get('/directory', [EmployeeController::class, 'index'])->name('hr.employees.index');
        Route::get('/directory/create', [EmployeeController::class, 'create'])
            ->middleware('permission:employees.manage')
            ->name('hr.employees.create');
        Route::post('/directory', [EmployeeController::class, 'store'])
            ->middleware('permission:employees.manage')
            ->name('hr.employees.store');
        Route::get('/directory/{employee}', [EmployeeController::class, 'show'])->name('hr.employees.show');
        Route::get('/directory/{employee}/edit', [EmployeeController::class, 'edit'])
            ->middleware('permission:employees.manage')
            ->name('hr.employees.edit');
        Route::put('/directory/{employee}', [EmployeeController::class, 'update'])
            ->middleware('permission:employees.manage')
            ->name('hr.employees.update');

        Route::get('/production-assignments', [ProductionAssignmentController::class, 'index'])->name('hr.production-assignments.index');
        Route::get('/production-assignments/create', [ProductionAssignmentController::class, 'create'])
            ->middleware('permission:employees.manage')
            ->name('hr.production-assignments.create');
        Route::post('/production-assignments', [ProductionAssignmentController::class, 'store'])
            ->middleware('permission:employees.manage')
            ->name('hr.production-assignments.store');
        Route::get('/production-assignments/{production_assignment}/edit', [ProductionAssignmentController::class, 'edit'])
            ->middleware('permission:employees.manage')
            ->name('hr.production-assignments.edit');
        Route::put('/production-assignments/{production_assignment}', [ProductionAssignmentController::class, 'update'])
            ->middleware('permission:employees.manage')
            ->name('hr.production-assignments.update');

        Route::get('/reports/department-roster', [HrReportController::class, 'departmentEmployees'])
            ->name('hr.reports.department-employees');
    });

    Route::middleware('permission:attendance.view')->prefix('attendance')->group(function () {
        Route::get('/', AttendanceHubController::class)->name('erp.attendance');

        Route::get('/register', [AttendanceController::class, 'index'])->name('hr.attendance.index');
        Route::get('/register/create', [AttendanceController::class, 'create'])
            ->middleware('permission:attendance.manage')
            ->name('hr.attendance.create');
        Route::post('/register', [AttendanceController::class, 'store'])
            ->middleware('permission:attendance.manage')
            ->name('hr.attendance.store');
        Route::get('/register/{attendance}/edit', [AttendanceController::class, 'edit'])
            ->middleware('permission:attendance.manage')
            ->name('hr.attendance.edit');
        Route::put('/register/{attendance}', [AttendanceController::class, 'update'])
            ->middleware('permission:attendance.manage')
            ->name('hr.attendance.update');

        Route::get('/daily', [AttendanceController::class, 'daily'])->name('hr.attendance.daily');
        Route::post('/daily', [AttendanceController::class, 'storeDaily'])
            ->middleware('permission:attendance.manage')
            ->name('hr.attendance.daily.store');

        Route::get('/monthly-report', [AttendanceController::class, 'monthly'])->name('hr.attendance.monthly');

        Route::get('/overtime', [OvertimeController::class, 'index'])->name('hr.overtime.index');
        Route::post('/overtime', [OvertimeController::class, 'store'])
            ->middleware('permission:attendance.manage')
            ->name('hr.overtime.store');
        Route::post('/overtime/{overtime}/approve', [OvertimeController::class, 'approve'])
            ->name('hr.overtime.approve');
    });

    Route::middleware('permission:payroll.view')->prefix('payroll')->group(function () {
        Route::get('/', PayrollHubController::class)->name('erp.payroll');

        Route::get('/runs', [PayrollRunController::class, 'index'])->name('hr.payroll.runs.index');
        Route::get('/runs/create', [PayrollRunController::class, 'create'])
            ->middleware('permission:payroll.manage')
            ->name('hr.payroll.runs.create');
        Route::post('/runs', [PayrollRunController::class, 'store'])
            ->middleware('permission:payroll.manage')
            ->name('hr.payroll.runs.store');
        Route::get('/runs/{payrollRun}', [PayrollRunController::class, 'show'])->name('hr.payroll.runs.show');
        Route::post('/runs/{payrollRun}/generate', [PayrollRunController::class, 'generate'])
            ->middleware('permission:payroll.manage')
            ->name('hr.payroll.runs.generate');
        Route::post('/runs/{payrollRun}/review', [PayrollRunController::class, 'review'])
            ->middleware('permission:payroll.manage')
            ->name('hr.payroll.runs.review');
        Route::post('/runs/{payrollRun}/approve', [PayrollRunController::class, 'approve'])
            ->name('hr.payroll.runs.approve');
        Route::post('/runs/{payrollRun}/cancel', [PayrollRunController::class, 'cancel'])
            ->name('hr.payroll.runs.cancel');

        Route::get('/records/{payrollRecord}', [PayrollRecordController::class, 'show'])->name('hr.payroll.records.show');
        Route::get('/records/{payrollRecord}/payslip', [PayrollRecordController::class, 'payslip'])->name('hr.payroll.records.payslip');
        Route::post('/records/{payrollRecord}/recalculate', [PayrollRecordController::class, 'recalculate'])
            ->middleware('permission:payroll.manage')
            ->name('hr.payroll.records.recalculate');
        Route::put('/records/{payrollRecord}/lines', [PayrollRecordController::class, 'updateLines'])
            ->middleware('permission:payroll.manage')
            ->name('hr.payroll.records.lines');
        Route::post('/records/{payrollRecord}/payments', [PayrollRecordController::class, 'storePayment'])
            ->middleware('permission:payroll.manage')
            ->name('hr.payroll.records.payments.store');

        Route::get('/advances', [EmployeeAdvanceController::class, 'index'])->name('hr.payroll.advances.index');
        Route::get('/advances/create', [EmployeeAdvanceController::class, 'create'])
            ->middleware('permission:payroll.manage')
            ->name('hr.payroll.advances.create');
        Route::post('/advances', [EmployeeAdvanceController::class, 'store'])
            ->middleware('permission:payroll.manage')
            ->name('hr.payroll.advances.store');

        Route::get('/loans', [EmployeeLoanController::class, 'index'])->name('hr.payroll.loans.index');
        Route::get('/loans/create', [EmployeeLoanController::class, 'create'])
            ->middleware('permission:payroll.manage')
            ->name('hr.payroll.loans.create');
        Route::post('/loans', [EmployeeLoanController::class, 'store'])
            ->middleware('permission:payroll.manage')
            ->name('hr.payroll.loans.store');

        Route::get('/reports/outstanding', [PayrollReportController::class, 'outstanding'])
            ->name('hr.payroll.reports.outstanding');
    });

    Route::middleware('permission:purchases.view')->prefix('purchases')->group(function () {
        Route::get('/', PurchaseHubController::class)->name('erp.purchases');

        Route::get('/orders', [PurchaseOrderController::class, 'index'])->name('purchases.orders.index');
        Route::get('/orders/create', [PurchaseOrderController::class, 'create'])
            ->middleware('permission:purchases.manage')
            ->name('purchases.orders.create');
        Route::post('/orders', [PurchaseOrderController::class, 'store'])
            ->middleware('permission:purchases.manage')
            ->name('purchases.orders.store');
        Route::get('/orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchases.orders.show');
        Route::get('/orders/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])
            ->middleware('permission:purchases.manage')
            ->name('purchases.orders.edit');
        Route::put('/orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])
            ->middleware('permission:purchases.manage')
            ->name('purchases.orders.update');
        Route::post('/orders/{purchaseOrder}/confirm', [PurchaseOrderController::class, 'confirm'])
            ->middleware('permission:purchases.manage')
            ->name('purchases.orders.confirm');
        Route::post('/orders/{purchaseOrder}/goods-receipts', [PurchaseOrderController::class, 'storeGoodsReceipt'])
            ->middleware('permission:purchases.manage')
            ->name('purchases.orders.goods-receipts.store');

        Route::get('/goods-receipts', [GoodsReceiptController::class, 'index'])->name('purchases.goods-receipts.index');
        Route::get('/goods-receipts/{goodsReceipt}', [GoodsReceiptController::class, 'show'])->name('purchases.goods-receipts.show');
        Route::post('/goods-receipts/{goodsReceipt}/post', [GoodsReceiptController::class, 'post'])
            ->middleware('permission:purchases.manage')
            ->name('purchases.goods-receipts.post');

        Route::get('/invoices', [PurchaseInvoiceController::class, 'index'])->name('purchases.invoices.index');
        Route::get('/invoices/create', [PurchaseInvoiceController::class, 'create'])
            ->middleware('permission:purchases.manage')
            ->name('purchases.invoices.create');
        Route::post('/invoices', [PurchaseInvoiceController::class, 'store'])
            ->middleware('permission:purchases.manage')
            ->name('purchases.invoices.store');
        Route::get('/invoices/{purchaseInvoice}', [PurchaseInvoiceController::class, 'show'])->name('purchases.invoices.show');
        Route::post('/invoices/{purchaseInvoice}/post', [PurchaseInvoiceController::class, 'post'])
            ->middleware('permission:purchases.manage')
            ->name('purchases.invoices.post');
        Route::post('/orders/{purchaseOrder}/invoices', [PurchaseInvoiceController::class, 'createFromOrder'])
            ->middleware('permission:purchases.manage')
            ->name('purchases.orders.invoices.store');

        Route::get('/payments', [SupplierPaymentController::class, 'index'])->name('purchases.payments.index');
        Route::get('/payments/create', [SupplierPaymentController::class, 'create'])
            ->middleware('permission:purchases.manage')
            ->name('purchases.payments.create');
        Route::post('/payments', [SupplierPaymentController::class, 'store'])
            ->middleware('permission:purchases.manage')
            ->name('purchases.payments.store');

        Route::get('/returns', [PurchaseReturnController::class, 'index'])->name('purchases.returns.index');
        Route::get('/returns/create', [PurchaseReturnController::class, 'create'])
            ->middleware('permission:purchases.manage')
            ->name('purchases.returns.create');
        Route::post('/returns', [PurchaseReturnController::class, 'store'])
            ->middleware('permission:purchases.manage')
            ->name('purchases.returns.store');
        Route::get('/returns/{purchaseReturn}', [PurchaseReturnController::class, 'show'])->name('purchases.returns.show');
        Route::post('/returns/{purchaseReturn}/post', [PurchaseReturnController::class, 'post'])
            ->middleware('permission:purchases.manage')
            ->name('purchases.returns.post');

        Route::get('/reports/outstanding', [PurchaseReportController::class, 'outstanding'])
            ->name('purchases.reports.outstanding');
    });

    Route::middleware('permission:suppliers.view')->prefix('suppliers')->group(function () {
        Route::get('/', SupplierHubController::class)->name('erp.suppliers');

        Route::get('/list', [SupplierController::class, 'index'])->name('purchases.suppliers.index');
        Route::get('/create', [SupplierController::class, 'create'])
            ->middleware('permission:suppliers.manage')
            ->name('purchases.suppliers.create');
        Route::post('/', [SupplierController::class, 'store'])
            ->middleware('permission:suppliers.manage')
            ->name('purchases.suppliers.store');
        Route::get('/{supplier}', [SupplierController::class, 'show'])->name('purchases.suppliers.show');
        Route::get('/{supplier}/edit', [SupplierController::class, 'edit'])
            ->middleware('permission:suppliers.manage')
            ->name('purchases.suppliers.edit');
        Route::put('/{supplier}', [SupplierController::class, 'update'])
            ->middleware('permission:suppliers.manage')
            ->name('purchases.suppliers.update');
    });

    Route::middleware('permission:sales.view')->prefix('sales')->group(function () {
        Route::get('/', SalesHubController::class)->name('erp.sales');

        Route::get('/orders', [SalesOrderController::class, 'index'])->name('sales.orders.index');
        Route::get('/orders/create', [SalesOrderController::class, 'create'])
            ->middleware('permission:sales.manage')
            ->name('sales.orders.create');
        Route::post('/orders', [SalesOrderController::class, 'store'])
            ->middleware('permission:sales.manage')
            ->name('sales.orders.store');
        Route::get('/orders/{salesOrder}', [SalesOrderController::class, 'show'])->name('sales.orders.show');
        Route::get('/orders/{salesOrder}/edit', [SalesOrderController::class, 'edit'])
            ->middleware('permission:sales.manage')
            ->name('sales.orders.edit');
        Route::put('/orders/{salesOrder}', [SalesOrderController::class, 'update'])
            ->middleware('permission:sales.manage')
            ->name('sales.orders.update');
        Route::post('/orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])
            ->middleware('permission:sales.manage')
            ->name('sales.orders.confirm');
        Route::post('/orders/{salesOrder}/invoices', [SalesOrderController::class, 'storeInvoice'])
            ->middleware('permission:sales.manage')
            ->name('sales.orders.invoices.store');

        Route::get('/invoices', [SalesInvoiceController::class, 'index'])->name('sales.invoices.index');
        Route::get('/invoices/create', [SalesInvoiceController::class, 'create'])
            ->middleware('permission:sales.manage')
            ->name('sales.invoices.create');
        Route::post('/invoices', [SalesInvoiceController::class, 'store'])
            ->middleware('permission:sales.manage')
            ->name('sales.invoices.store');
        Route::get('/invoices/{salesInvoice}', [SalesInvoiceController::class, 'show'])->name('sales.invoices.show');
        Route::get('/invoices/{salesInvoice}/print', [SalesInvoiceController::class, 'print'])->name('sales.invoices.print');
        Route::post('/invoices/{salesInvoice}/post', [SalesInvoiceController::class, 'post'])
            ->middleware('permission:sales.manage')
            ->name('sales.invoices.post');

        Route::get('/receipts', [CustomerReceiptController::class, 'index'])->name('sales.receipts.index');
        Route::get('/receipts/create', [CustomerReceiptController::class, 'create'])
            ->middleware('permission:sales.manage')
            ->name('sales.receipts.create');
        Route::post('/receipts', [CustomerReceiptController::class, 'store'])
            ->middleware('permission:sales.manage')
            ->name('sales.receipts.store');

        Route::get('/returns', [SalesReturnController::class, 'index'])->name('sales.returns.index');
        Route::get('/returns/create', [SalesReturnController::class, 'create'])
            ->middleware('permission:sales.manage')
            ->name('sales.returns.create');
        Route::post('/returns', [SalesReturnController::class, 'store'])
            ->middleware('permission:sales.manage')
            ->name('sales.returns.store');
        Route::get('/returns/{salesReturn}', [SalesReturnController::class, 'show'])->name('sales.returns.show');
        Route::post('/returns/{salesReturn}/post', [SalesReturnController::class, 'post'])
            ->middleware('permission:sales.manage')
            ->name('sales.returns.post');

        Route::get('/reports/outstanding', [SalesReportController::class, 'outstanding'])
            ->name('sales.reports.outstanding');
        Route::get('/reports/summary', [SalesReportController::class, 'summary'])
            ->name('sales.reports.summary');
    });

    Route::middleware('permission:customers.view')->prefix('customers')->group(function () {
        Route::get('/', CustomerHubController::class)->name('erp.customers');

        Route::get('/list', [CustomerController::class, 'index'])->name('sales.customers.index');
        Route::get('/create', [CustomerController::class, 'create'])
            ->middleware('permission:customers.manage')
            ->name('sales.customers.create');
        Route::post('/', [CustomerController::class, 'store'])
            ->middleware('permission:customers.manage')
            ->name('sales.customers.store');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('sales.customers.show');
        Route::get('/{customer}/edit', [CustomerController::class, 'edit'])
            ->middleware('permission:customers.manage')
            ->name('sales.customers.edit');
        Route::put('/{customer}', [CustomerController::class, 'update'])
            ->middleware('permission:customers.manage')
            ->name('sales.customers.update');
    });

    Route::middleware('permission:expenses.view')->prefix('expenses')->group(function () {
        Route::get('/', ExpensesHubController::class)->name('erp.expenses');
        Route::get('/entries', [ExpenseController::class, 'index'])->name('expenses.entries.index');
        Route::get('/entries/create', [ExpenseController::class, 'create'])
            ->middleware('permission:expenses.manage')
            ->name('expenses.entries.create');
        Route::post('/entries', [ExpenseController::class, 'store'])
            ->middleware('permission:expenses.manage')
            ->name('expenses.entries.store');
    });

    Route::middleware('permission:accounting.view')->prefix('accounting')->group(function () {
        Route::get('/', AccountingHubController::class)->name('erp.accounting');

        Route::get('/gl-accounts', [GlAccountController::class, 'index'])->name('accounting.gl-accounts.index');
        Route::get('/journals', [JournalEntryController::class, 'index'])->name('accounting.journals.index');
        Route::get('/journals/{journalEntry}', [JournalEntryController::class, 'show'])->name('accounting.journals.show');

        Route::get('/treasury', [TreasuryAccountController::class, 'index'])->name('accounting.treasury.index');
        Route::post('/treasury/transfer', [TreasuryAccountController::class, 'storeTransfer'])
            ->middleware('permission:accounting.manage')
            ->name('accounting.treasury.transfer');

        Route::get('/company-loans', [CompanyLoanController::class, 'index'])->name('accounting.company-loans.index');
        Route::get('/company-loans/create', [CompanyLoanController::class, 'create'])
            ->middleware('permission:accounting.manage')
            ->name('accounting.company-loans.create');
        Route::post('/company-loans', [CompanyLoanController::class, 'store'])
            ->middleware('permission:accounting.manage')
            ->name('accounting.company-loans.store');
        Route::post('/company-loans/{companyLoan}/repayments', [CompanyLoanController::class, 'storeRepayment'])
            ->middleware('permission:accounting.manage')
            ->name('accounting.company-loans.repayments.store');

        Route::get('/reports/trial-balance', [FinancialReportController::class, 'trialBalance'])
            ->name('accounting.reports.trial-balance');
        Route::get('/reports/profit-loss', [FinancialReportController::class, 'profitAndLoss'])
            ->name('accounting.reports.profit-loss');
        Route::get('/reports/balance-sheet', [FinancialReportController::class, 'balanceSheet'])
            ->name('accounting.reports.balance-sheet');
        Route::get('/reports/ledger', [FinancialReportController::class, 'ledger'])
            ->name('accounting.reports.ledger');
    });

    Route::middleware('permission:reports.view')->prefix('reports')->group(function () {
        Route::get('/', ReportsHubController::class)->name('erp.reports');
        Route::get('/analytics/{reportKey}', [ErpReportController::class, 'show'])
            ->where('reportKey', '[a-z0-9\-]+')
            ->name('erp.reports.show');
        Route::get('/analytics/{reportKey}/export/{format}', [ErpReportController::class, 'export'])
            ->where(['reportKey' => '[a-z0-9\-]+', 'format' => 'csv|print|pdf'])
            ->name('erp.reports.export');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';