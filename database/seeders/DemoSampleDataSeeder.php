<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\BagBillOfMaterial;
use App\Models\BagBomLine;
use App\Models\BagProduct;
use App\Models\BagProductVariant;
use App\Models\BagProductionBatch;
use App\Models\BagProductionOrder;
use App\Models\Color;
use App\Models\Customer;
use App\Models\CustomerReceipt;
use App\Models\DanaBatch;
use App\Models\DanaProductionRecipe;
use App\Models\DanaProductionRecipeLine;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeLoan;
use App\Models\EmployeeProductionAssignment;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\GlAccount;
use App\Models\GoodsReceipt;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\Machine;
use App\Models\MachineType;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\OvertimeRecord;
use App\Models\PayrollPayment;
use App\Models\PayrollRun;
use App\Models\ProductCategory;
use App\Models\ProductionHall;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\TreasuryAccount;
use App\Models\TreasuryMovement;
use App\Models\UnitOfMeasurement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting\TreasuryService;
use App\Services\Bag\BagBatchPersistenceService;
use App\Services\Bag\BagBatchPostingService;
use App\Services\Bag\BagBatchWorkflowService;
use App\Services\Bag\BagOrderPersistenceService;
use App\Services\Bag\BagOrderWorkflowService;
use App\Services\Dana\DanaBatchPersistenceService;
use App\Services\Dana\DanaBatchPostingService;
use App\Services\Dana\DanaBatchWorkflowService;
use App\Services\Hr\AdvanceLoanService;
use App\Services\Hr\AttendanceService;
use App\Services\Hr\EmployeePersistenceService;
use App\Services\Hr\EmployeeProductionAssignmentService;
use App\Services\Hr\OvertimeService;
use App\Services\Hr\PayrollPaymentService;
use App\Services\Hr\PayrollRunService;
use App\Services\Inventory\InventoryPostingService;
use App\Services\Inventory\InventoryTransactionNumberGenerator;
use App\Services\Purchases\GoodsReceiptService;
use App\Services\Purchases\PurchaseInvoiceService;
use App\Services\Purchases\PurchaseOrderService;
use App\Services\Purchases\SupplierPaymentService;
use App\Services\Purchases\SupplierPersistenceService;
use App\Services\Sales\CustomerPersistenceService;
use App\Services\Sales\CustomerReceiptService;
use App\Services\Sales\SalesInvoiceService;
use App\Services\Sales\SalesOrderService;
use App\Services\Accounting\ExpenseService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Idempotent DEMO rows for a presentable dashboard and public catalog.
 * Re-running updates nothing that is already posted under a DEMO- marker.
 */
class DemoSampleDataSeeder extends Seeder
{
    /** @var list<string> */
    private array $notes = [];

    private User $actor;

    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->requireTables([
            'units_of_measurement',
            'materials',
            'inventory_transactions',
            'dana_batches',
            'bag_products',
            'bag_production_batches',
            'employees',
            'attendances',
            'payroll_runs',
            'suppliers',
            'purchase_orders',
            'customers',
            'sales_invoices',
            'expenses',
            'gl_accounts',
            'treasury_accounts',
        ]);

        $this->actor = $this->actor();
        $today = now()->toDateString();

        $masters = DB::transaction(fn () => $this->masters());
        $materials = DB::transaction(fn () => $this->materials($masters));
        $people = DB::transaction(fn () => $this->people($masters));

        $this->openingCash();
        $this->openingStock($masters['warehouses']['raw'], $materials);
        $this->purchases($masters, $materials, $today);
        $this->danaBatches($masters, $materials, $people, $today);
        $bags = $this->bagProducts($masters, $materials);
        $this->bagProduction($masters, $materials, $bags, $people, $today);
        $this->sales($masters, $people, $bags, $today);
        $this->expenses($masters, $today);
        $this->attendanceAndPayroll($people, $today);
        $this->publishCatalog($bags, $materials['dana']);
        $this->report();
    }

    /**
     * @param  list<string>  $tables
     */
    private function requireTables(array $tables): void
    {
        $missing = array_values(array_filter($tables, fn (string $table) => ! Schema::hasTable($table)));
        if ($missing !== []) {
            throw new \RuntimeException('Demo seeder stopped. Missing tables: '.implode(', ', $missing).'. Run additive migrations first.');
        }
    }

    private function actor(): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'owner@bagfactory.local'],
            [
                'name' => 'System Owner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        if (! $user->hasRole('owner-admin')) {
            $user->assignRole('owner-admin');
        }

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function masters(): array
    {
        $kg = UnitOfMeasurement::query()->firstOrCreate(
            ['code' => 'KG'],
            ['name' => 'Kilogram', 'symbol' => 'kg', 'status' => 'active'],
        );
        $pcs = UnitOfMeasurement::query()->firstOrCreate(
            ['code' => 'PCS'],
            ['name' => 'Piece', 'symbol' => 'pcs', 'status' => 'active'],
        );

        $hallDana = $this->master(ProductionHall::class, 'DEMO-HALL-DANA', 'DEMO Dana hall');
        $hallBag = $this->master(ProductionHall::class, 'DEMO-HALL-BAG', 'DEMO Bag hall');
        $typeDana = $this->master(MachineType::class, 'DEMO-EXTR', 'DEMO Extruder');
        $typeBag = $this->master(MachineType::class, 'DEMO-BAGM', 'DEMO Bag machine');

        $colors = [
            'white' => $this->color('DEMO-WHT', 'DEMO White', '#f8fafc'),
            'blue' => $this->color('DEMO-BLU', 'DEMO Blue', '#1d4ed8'),
            'red' => $this->color('DEMO-RED', 'DEMO Red', '#b91c1c'),
        ];

        $categories = [
            'raw' => $this->master(MaterialCategory::class, 'DEMO-RAW', 'DEMO Raw polymer'),
            'dana' => $this->master(MaterialCategory::class, 'DEMO-DANA', 'DEMO Dana'),
            'bag' => $this->master(MaterialCategory::class, 'DEMO-BAG', 'DEMO Finished bags'),
            'other' => $this->master(MaterialCategory::class, 'DEMO-OTH', 'DEMO Other materials'),
        ];

        $this->master(ProductCategory::class, 'DEMO-PC-BAG', 'DEMO Shopping bags');
        $this->master(ProductCategory::class, 'DEMO-PC-DANA', 'DEMO Dana');

        $department = $this->master(Department::class, 'DEMO-PROD', 'DEMO Production');
        $operatorRole = Designation::query()->updateOrCreate(
            ['code' => 'DEMO-OP'],
            ['name' => 'DEMO Machine operator', 'department_id' => $department->id, 'status' => 'active'],
        );
        $helperRole = Designation::query()->updateOrCreate(
            ['code' => 'DEMO-HELP'],
            ['name' => 'DEMO Helper', 'department_id' => $department->id, 'status' => 'active'],
        );

        $warehouses = [
            'raw' => $this->warehouse('DEMO-RAW', 'DEMO Raw store'),
            'dana' => $this->warehouse('DEMO-DANA', 'DEMO Dana store'),
            'fg' => $this->warehouse('DEMO-FG', 'DEMO Finished bags'),
            'rej' => $this->warehouse('DEMO-REJ', 'DEMO Rejected bags'),
            'waste' => $this->warehouse('DEMO-WASTE', 'DEMO Waste store'),
        ];

        $extruder = Machine::query()->updateOrCreate(
            ['code' => 'DEMO-EXT-1'],
            [
                'name' => 'DEMO Extruder 1',
                'machine_type_id' => $typeDana->id,
                'production_hall_id' => $hallDana->id,
                'capacity' => null,
                'capacity_unit_id' => $kg->id,
                'status' => 'active',
                'notes' => 'DEMO sample machine. Capacity is left blank.',
            ],
        );
        $bagLine = Machine::query()->updateOrCreate(
            ['code' => 'DEMO-BAG-1'],
            [
                'name' => 'DEMO Bag line 1',
                'machine_type_id' => $typeBag->id,
                'production_hall_id' => $hallBag->id,
                'capacity' => null,
                'capacity_unit_id' => $pcs->id,
                'status' => 'active',
                'notes' => 'DEMO sample machine. Capacity is left blank.',
            ],
        );

        $factoryGl = GlAccount::query()->where('code', '6000')->value('id');
        $expenseCategories = [];
        foreach ([
            'DEMO-ELEC' => 'DEMO Electricity',
            'DEMO-RENT' => 'DEMO Rent',
            'DEMO-TRN' => 'DEMO Transport',
            'DEMO-MNT' => 'DEMO Maintenance',
            'DEMO-UTL' => 'DEMO Utilities',
        ] as $code => $name) {
            $expenseCategories[$code] = ExpenseCategory::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'description' => 'DEMO sample category. Not a salary category.',
                    'gl_account_id' => $factoryGl,
                    'status' => 'active',
                ],
            );
        }

        return [
            'kg' => $kg,
            'pcs' => $pcs,
            'halls' => ['dana' => $hallDana, 'bag' => $hallBag],
            'colors' => $colors,
            'categories' => $categories,
            'department' => $department,
            'designations' => ['operator' => $operatorRole, 'helper' => $helperRole],
            'warehouses' => $warehouses,
            'machines' => ['extruder' => $extruder, 'bag' => $bagLine],
            'expenseCategories' => $expenseCategories,
        ];
    }

    /**
     * @param  array<string, mixed>  $masters
     * @return array<string, Material>
     */
    private function materials(array $masters): array
    {
        $kg = $masters['kg'];
        $pcs = $masters['pcs'];
        $categories = $masters['categories'];

        $rows = [
            'ldpe' => ['DEMO-LDPE', 'DEMO LDPE granules', $categories['raw'], $kg, 'raw', 180, 40],
            'hdpe' => ['DEMO-HDPE', 'DEMO HDPE granules', $categories['raw'], $kg, 'raw', 170, 30],
            'recycled' => ['DEMO-RECY', 'DEMO Recycled granules', $categories['raw'], $kg, 'raw', 90, 20],
            'masterbatch' => ['DEMO-MB', 'DEMO Masterbatch', $categories['raw'], $kg, 'raw', 400, 5],
            'ink' => ['DEMO-INK', 'DEMO Printing ink', $categories['other'], $kg, 'raw', 550, 2],
            'handles' => ['DEMO-HANDLE', 'DEMO Bag handles', $categories['other'], $pcs, 'raw', 2, 500],
            'packaging' => ['DEMO-PACK', 'DEMO Packing film', $categories['other'], $pcs, 'raw', 15, 20],
            'dana' => ['DEMO-DAN-NAT', 'DEMO Natural dana', $categories['dana'], $kg, 'dana', 0, 25],
            'reuse' => ['DEMO-WASTE-REUSE', 'DEMO Reusable waste', $categories['other'], $kg, 'other', 0, 0],
            'scrap' => ['DEMO-WASTE-SCRAP', 'DEMO Scrap waste', $categories['other'], $kg, 'other', 0, 0],
            'fg_plain' => ['DEMO-FG-PLAIN', 'DEMO Plain bag pieces', $categories['bag'], $pcs, 'finished_bag', 0, 0],
            'fg_print' => ['DEMO-FG-PRINT', 'DEMO Printed bag pieces', $categories['bag'], $pcs, 'finished_bag', 0, 0],
            'rej_plain' => ['DEMO-REJ-PLAIN', 'DEMO Rejected plain bags', $categories['bag'], $pcs, 'rejected_bag', 0, 0],
            'rej_print' => ['DEMO-REJ-PRINT', 'DEMO Rejected printed bags', $categories['bag'], $pcs, 'rejected_bag', 0, 0],
        ];

        $materials = [];
        foreach ($rows as $key => [$code, $name, $category, $unit, $bucket, $cost, $reorder]) {
            $materials[$key] = Material::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'material_category_id' => $category->id,
                    'unit_of_measurement_id' => $unit->id,
                    'default_unit_cost' => $cost,
                    'reorder_level' => $reorder,
                    'inventory_bucket' => $bucket,
                    'description' => 'DEMO sample material. Not a published specification.',
                    'status' => 'active',
                ],
            );
        }

        $recipe = DanaProductionRecipe::query()->updateOrCreate(
            ['code' => 'DEMO-RCP-NAT'],
            [
                'name' => 'DEMO Natural dana mix',
                'output_material_id' => $materials['dana']->id,
                'max_process_loss_percent' => 5,
                'description' => 'Sample demo recipe. Not a verified formulation.',
                'status' => 'active',
            ],
        );
        $this->recipeLine($recipe, $materials['ldpe'], 0.70, 1);
        $this->recipeLine($recipe, $materials['hdpe'], 0.20, 2);
        $this->recipeLine($recipe, $materials['masterbatch'], 0.05, 3);
        $this->recipeLine($recipe, $materials['recycled'], 0.05, 4);
        $materials['recipe'] = $recipe;

        return $materials;
    }

    private function recipeLine(DanaProductionRecipe $recipe, Material $material, float $perKg, int $order): void
    {
        DanaProductionRecipeLine::query()->updateOrCreate(
            [
                'dana_production_recipe_id' => $recipe->id,
                'material_id' => $material->id,
            ],
            [
                'input_type' => 'raw',
                'quantity_per_output_kg' => $perKg,
                'line_order' => $order,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $masters
     * @return array<string, mixed>
     */
    private function people(array $masters): array
    {
        $suppliers = [
            'polymer' => $this->supplier('DEMO-SUP-POLY', 'DEMO Polymer Supply'),
            'ink' => $this->supplier('DEMO-SUP-INK', 'DEMO Ink Supply'),
        ];
        $customers = [
            'retail' => $this->customer('DEMO-CUS-RETAIL', 'DEMO Retail Buyer'),
            'trade' => $this->customer('DEMO-CUS-TRADE', 'DEMO Trade Buyer'),
        ];

        $monthly = $this->employee('DEMO-EMP-01', 'DEMO Shift Operator', 'monthly', $masters, [
            'basic_monthly_salary' => 45000,
            'daily_wage_rate' => null,
            'overtime_rate' => 200,
            'designation_id' => $masters['designations']['operator']->id,
        ]);
        $daily = $this->employee('DEMO-EMP-02', 'DEMO Daily Helper', 'daily', $masters, [
            'basic_monthly_salary' => null,
            'daily_wage_rate' => 1500,
            'overtime_rate' => 150,
            'designation_id' => $masters['designations']['helper']->id,
        ]);

        return compact('suppliers', 'customers', 'monthly', 'daily');
    }

    private function openingCash(): void
    {
        if (TreasuryMovement::query()->where('notes', 'DEMO-CASH-OPEN')->exists()) {
            return;
        }

        $cash = TreasuryAccount::query()->where('code', 'CASH-01')->where('status', 'active')->first();
        if (! $cash) {
            $this->notes[] = 'Skipped DEMO cash deposit: treasury account CASH-01 is missing.';

            return;
        }

        app(TreasuryService::class)->recordCashMovement(
            account: $cash,
            movementType: 'receipt',
            movementDate: now()->toDateString(),
            amount: 250000,
            description: 'DEMO-CASH-OPEN',
            sourceType: 'demo_sample_cash',
            sourceId: 1,
            actor: $this->actor,
        );
    }

    /**
     * @param  array<string, Material>  $materials
     */
    private function openingStock(Warehouse $warehouse, array $materials): void
    {
        if (InventoryTransaction::query()->where('notes', 'DEMO-OS-RAW')->exists()) {
            return;
        }

        $lines = [
            [$materials['recycled'], 80, 90],
            [$materials['masterbatch'], 30, 400],
            [$materials['handles'], 5000, 2],
            [$materials['packaging'], 200, 15],
        ];

        $tx = InventoryTransaction::query()->create([
            'transaction_number' => app(InventoryTransactionNumberGenerator::class)->next('opening_stock'),
            'type' => 'opening_stock',
            'status' => 'draft',
            'warehouse_id' => $warehouse->id,
            'transaction_date' => now()->toDateString(),
            'notes' => 'DEMO-OS-RAW',
            'created_by' => $this->actor->id,
        ]);

        $order = 1;
        foreach ($lines as [$material, $qty, $cost]) {
            InventoryTransactionItem::query()->create([
                'inventory_transaction_id' => $tx->id,
                'material_id' => $material->id,
                'quantity' => $qty,
                'unit_cost' => $cost,
                'total_value' => round($qty * $cost, 4),
                'line_order' => $order++,
            ]);
        }

        app(InventoryPostingService::class)->post($tx->fresh('items'), $this->actor);
    }

    /**
     * @param  array<string, mixed>  $masters
     * @param  array<string, Material>  $materials
     */
    private function purchases(array $masters, array $materials, string $today): void
    {
        $this->purchaseCycle(
            marker: 'DEMO-PO-POLY',
            supplier: $this->supplier('DEMO-SUP-POLY', 'DEMO Polymer Supply'),
            warehouse: $masters['warehouses']['raw'],
            date: $today,
            lines: [
                ['material' => $materials['ldpe'], 'qty' => 200, 'price' => 180, 'discount' => 500, 'tax' => 0],
                ['material' => $materials['hdpe'], 'qty' => 150, 'price' => 170, 'discount' => 0, 'tax' => 0],
            ],
            payFraction: 0.5,
        );

        $this->purchaseCycle(
            marker: 'DEMO-PO-INK',
            supplier: $this->supplier('DEMO-SUP-INK', 'DEMO Ink Supply'),
            warehouse: $masters['warehouses']['raw'],
            date: $today,
            lines: [
                ['material' => $materials['ink'], 'qty' => 10, 'price' => 500, 'discount' => 0, 'tax' => 5],
            ],
            payFraction: 0,
        );
    }

    /**
     * @param  list<array{material: Material, qty: float, price: float, discount: float, tax: float}>  $lines
     */
    private function purchaseCycle(string $marker, Supplier $supplier, Warehouse $warehouse, string $date, array $lines, float $payFraction): void
    {
        DB::transaction(function () use ($marker, $supplier, $warehouse, $date, $lines, $payFraction) {
            $order = PurchaseOrder::query()->where('notes', $marker)->first();
            if (! $order) {
                $order = app(PurchaseOrderService::class)->createDraft([
                    'supplier_id' => $supplier->id,
                    'warehouse_id' => $warehouse->id,
                    'order_date' => $date,
                    'notes' => $marker,
                ], array_map(fn (array $line) => [
                    'material_id' => $line['material']->id,
                    'unit_of_measurement_id' => $line['material']->unit_of_measurement_id,
                    'ordered_qty' => $line['qty'],
                    'unit_price' => $line['price'],
                    'discount_amount' => $line['discount'],
                    'tax_rate' => $line['tax'],
                ], $lines), $this->actor);
            }

            if ($order->status === 'draft') {
                $order = app(PurchaseOrderService::class)->confirm($order, $this->actor);
            }

            $grn = GoodsReceipt::query()->where('notes', $marker)->first();
            if (! $grn) {
                $order->load('lines');
                $grn = app(GoodsReceiptService::class)->createDraft($order, $date, $order->lines->map(fn ($line) => [
                    'purchase_order_line_id' => $line->id,
                    'received_qty' => (float) $line->ordered_qty,
                ])->all(), $this->actor, $marker);
            }
            if ($grn->status === 'draft') {
                app(GoodsReceiptService::class)->post($grn, $this->actor);
            }

            $invoice = PurchaseInvoice::query()->where('notes', $marker)->first();
            if (! $invoice) {
                $order = $order->fresh('lines');
                $invoice = app(PurchaseInvoiceService::class)->createFromPurchaseOrder($order, [
                    'invoice_date' => $date,
                    'notes' => $marker,
                    'supplier_invoice_number' => $marker,
                ], $order->lines->map(fn ($line) => [
                    'purchase_order_line_id' => $line->id,
                    'quantity' => (float) $line->received_qty,
                ])->all(), $this->actor);
            }
            if ($invoice->status === 'draft') {
                $invoice = app(PurchaseInvoiceService::class)->post($invoice, $this->actor);
            }

            $payMarker = $marker.'-PAY';
            if ($payFraction > 0 && ! SupplierPayment::query()->where('notes', $payMarker)->exists()) {
                $invoice = $invoice->fresh();
                $amount = round((float) $invoice->grand_total * $payFraction, 2);
                if ($amount > 0 && (float) $invoice->amount_paid + 0.001 < (float) $invoice->grand_total) {
                    app(SupplierPaymentService::class)->record($supplier->fresh(), [
                        'payment_date' => $date,
                        'amount' => $amount,
                        'payment_method' => 'cash',
                        'notes' => $payMarker,
                        'reference' => $payMarker,
                    ], [
                        ['purchase_invoice_id' => $invoice->id, 'amount' => $amount],
                    ], $this->actor);
                }
            }
        });
    }

    /**
     * @param  array<string, mixed>  $masters
     * @param  array<string, mixed>  $materials
     * @param  array<string, mixed>  $people
     */
    private function danaBatches(array $masters, array $materials, array $people, string $today): void
    {
        $this->postDana('DEMO-DANA-01', $masters, $materials, $people['monthly'], $today, [
            'output_kg' => 94,
            'reusable_waste_kg' => 3,
            'scrap_waste_kg' => 1,
            'process_loss_kg' => 2,
            'inputs' => [
                ['material_id' => $materials['ldpe']->id, 'input_type' => 'raw', 'quantity_kg' => 70],
                ['material_id' => $materials['hdpe']->id, 'input_type' => 'raw', 'quantity_kg' => 20],
                ['material_id' => $materials['masterbatch']->id, 'input_type' => 'color', 'quantity_kg' => 5],
                ['material_id' => $materials['recycled']->id, 'input_type' => 'raw', 'quantity_kg' => 5],
            ],
        ]);

        $this->postDana('DEMO-DANA-02', $masters, $materials, $people['daily'], $today, [
            'output_kg' => 38,
            'reusable_waste_kg' => 1,
            'scrap_waste_kg' => 0,
            'process_loss_kg' => 1,
            'inputs' => [
                ['material_id' => $materials['recycled']->id, 'input_type' => 'raw', 'quantity_kg' => 40],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $masters
     * @param  array<string, mixed>  $materials
     * @param  array<string, mixed>  $payload
     */
    private function postDana(string $marker, array $masters, array $materials, Employee $operator, string $today, array $payload): void
    {
        $batch = DanaBatch::query()->where('notes', $marker)->first();
        if (! $batch) {
            $batch = app(DanaBatchPersistenceService::class)->create([
                'production_date' => $today,
                'machine_id' => $masters['machines']['extruder']->id,
                'production_hall_id' => $masters['halls']['dana']->id,
                'raw_material_warehouse_id' => $masters['warehouses']['raw']->id,
                'finished_dana_warehouse_id' => $masters['warehouses']['dana']->id,
                'waste_warehouse_id' => $masters['warehouses']['waste']->id,
                'output_material_id' => $materials['dana']->id,
                'dana_production_recipe_id' => $materials['recipe']->id,
                'output_kg' => $payload['output_kg'],
                'reusable_waste_kg' => $payload['reusable_waste_kg'],
                'scrap_waste_kg' => $payload['scrap_waste_kg'],
                'process_loss_kg' => $payload['process_loss_kg'],
                'electricity_cost' => 80,
                'other_overhead_cost' => 20,
                'reusable_waste_material_id' => $materials['reuse']->id,
                'scrap_waste_material_id' => $payload['scrap_waste_kg'] > 0 ? $materials['scrap']->id : null,
                'notes' => $marker,
                'inputs' => $payload['inputs'],
                'operators' => [
                    ['operator_name' => $operator->name, 'labor_cost' => 200, 'hours' => 8],
                ],
            ], $this->actor);
        }

        $this->advanceBatch($batch, DanaBatchWorkflowService::class, DanaBatchPostingService::class);
        $this->assignEmployee($operator, 'dana_batch', [
            'dana_batch_id' => $batch->fresh()->id,
            'machine_id' => $masters['machines']['extruder']->id,
            'notes' => $marker.'-ASG',
        ], $today);
    }

    /**
     * @param  array<string, mixed>  $masters
     * @param  array<string, Material>  $materials
     * @return array<string, array{product: BagProduct, variant: BagProductVariant}>
     */
    private function bagProducts(array $masters, array $materials): array
    {
        $plain = $this->bagProduct(
            'DEMO-BAG-PLAIN',
            'DEMO Plain T-shirt bag',
            't_shirt',
            '300 x 450 mm',
            300,
            450,
            40,
            8,
            $masters['colors']['white'],
            $masters['pcs'],
            'shopping-bags',
            'demo-plain-t-shirt-bag',
            1,
            $materials['fg_plain'],
            $materials['rej_plain'],
            $materials['dana'],
        );
        $printed = $this->bagProduct(
            'DEMO-BAG-PRINT',
            'DEMO Printed loop bag',
            'loop_handle',
            '400 x 450 mm',
            400,
            450,
            50,
            12,
            $masters['colors']['blue'],
            $masters['pcs'],
            'printed-bags',
            'demo-printed-loop-bag',
            2,
            $materials['fg_print'],
            $materials['rej_print'],
            $materials['dana'],
        );
        $custom = $this->bagProduct(
            'DEMO-BAG-CUSTOM',
            'DEMO Custom die-cut bag',
            'die_cut',
            '250 x 350 mm',
            250,
            350,
            30,
            6,
            $masters['colors']['red'],
            $masters['pcs'],
            'custom-bags',
            'demo-custom-die-cut-bag',
            3,
            $materials['fg_plain'],
            $materials['rej_plain'],
            $materials['dana'],
            false,
        );

        return [
            'plain' => $plain,
            'printed' => $printed,
            'custom' => $custom,
        ];
    }

    /**
     * @return array{product: BagProduct, variant: BagProductVariant}
     */
    private function bagProduct(
        string $code,
        string $name,
        string $type,
        string $size,
        float $width,
        float $length,
        float $thickness,
        float $grams,
        Color $color,
        UnitOfMeasurement $unit,
        string $category,
        string $slug,
        int $sort,
        Material $saleable,
        Material $rejected,
        Material $dana,
        bool $withBom = true,
    ): array {
        $copy = 'Sample catalog text. Sizes and colors on this card are demo values stored for the sample catalog. They are not a verified factory specification.';
        $product = BagProduct::query()->updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'bag_type' => $type,
                'size_label' => $size,
                'width_mm' => $width,
                'length_mm' => $length,
                'thickness_micron' => $thickness,
                'nominal_weight_per_bag_g' => $grams,
                'color_id' => $color->id,
                'unit_of_measurement_id' => $unit->id,
                'selling_price' => 0,
                'status' => 'active',
                'description' => 'DEMO sample product. Internal record only.',
                'is_public' => true,
                'public_slug' => $slug,
                'public_category' => $category,
                'public_summary' => $copy,
                'public_description' => $copy.' Printing and other changes are requested on the quote form. This record has no minimum-order field.',
                'public_sort' => $sort,
            ],
        );

        $variant = BagProductVariant::query()->updateOrCreate(
            ['bag_product_id' => $product->id, 'variant_code' => 'DEMO'],
            [
                'name' => $color->name,
                'color_id' => $color->id,
                'weight_per_bag_g' => $grams,
                'saleable_material_id' => $saleable->id,
                'rejected_material_id' => $rejected->id,
                'selling_price' => 0,
                'status' => 'active',
            ],
        );

        if ($withBom) {
            $bom = BagBillOfMaterial::query()->updateOrCreate(
                ['code' => $code.'-BOM'],
                [
                    'name' => $name.' BOM',
                    'bag_product_id' => $product->id,
                    'bag_product_variant_id' => $variant->id,
                    'version' => 1,
                    'effective_from' => now()->toDateString(),
                    'status' => 'active',
                    'notes' => 'DEMO sample bill of material.',
                ],
            );
            BagBomLine::query()->updateOrCreate(
                ['bag_bill_of_material_id' => $bom->id, 'material_id' => $dana->id],
                [
                    'consumption_unit' => 'per_bag',
                    'quantity_per_bag' => round($grams / 1000, 6),
                    'wastage_percent' => 0,
                    'line_order' => 1,
                ],
            );
        }

        return ['product' => $product->fresh(), 'variant' => $variant->fresh()];
    }

    /**
     * @param  array<string, mixed>  $masters
     * @param  array<string, mixed>  $materials
     * @param  array<string, array{product: BagProduct, variant: BagProductVariant}>  $bags
     * @param  array<string, mixed>  $people
     */
    private function bagProduction(array $masters, array $materials, array $bags, array $people, string $today): void
    {
        $this->postBag('DEMO-BORD-PLAIN', 'DEMO-BBAT-PLAIN', $masters, $materials, $bags['plain'], $people['monthly'], $today, [
            'planned' => 5000,
            'good' => 1000,
            'rejected' => 25,
            'grams' => 8,
            'dana' => 8.20,
            'color' => 0.30,
            'reusable' => 0.16,
            'scrap' => 0.04,
            'loss' => 0.10,
        ]);
        $this->postBag('DEMO-BORD-PRINT', 'DEMO-BBAT-PRINT', $masters, $materials, $bags['printed'], $people['daily'], $today, [
            'planned' => 2000,
            'good' => 500,
            'rejected' => 10,
            'grams' => 12,
            'dana' => 6.12,
            'color' => 0.20,
            'reusable' => 0.10,
            'scrap' => 0.04,
            'loss' => 0.06,
        ]);
    }

    /**
     * @param  array<string, mixed>  $masters
     * @param  array<string, mixed>  $materials
     * @param  array{product: BagProduct, variant: BagProductVariant}  $bag
     * @param  array{planned: float, good: float, rejected: float, grams: float, dana: float, color: float, reusable: float, scrap: float, loss: float}  $qty
     */
    private function postBag(string $orderMarker, string $batchMarker, array $masters, array $materials, array $bag, Employee $operator, string $today, array $qty): void
    {
        $bomId = BagBillOfMaterial::query()->where('code', $bag['product']->code.'-BOM')->value('id');
        $order = BagProductionOrder::query()->where('notes', $orderMarker)->first();
        if (! $order) {
            $order = app(BagOrderPersistenceService::class)->create([
                'bag_product_id' => $bag['product']->id,
                'bag_product_variant_id' => $bag['variant']->id,
                'bag_bill_of_material_id' => $bomId,
                'planned_quantity' => $qty['planned'],
                'machine_id' => $masters['machines']['bag']->id,
                'production_hall_id' => $masters['halls']['bag']->id,
                'color_id' => $bag['product']->color_id,
                'planned_start_date' => $today,
                'finished_goods_warehouse_id' => $masters['warehouses']['fg']->id,
                'dana_warehouse_id' => $masters['warehouses']['dana']->id,
                'materials_warehouse_id' => $masters['warehouses']['raw']->id,
                'rejected_warehouse_id' => $masters['warehouses']['rej']->id,
                'waste_warehouse_id' => $masters['warehouses']['waste']->id,
                'notes' => $orderMarker,
                'assignments' => [
                    ['employee_name' => $operator->name, 'role' => 'operator'],
                ],
            ], $this->actor);
        }
        if ($order->status === 'draft') {
            app(BagOrderWorkflowService::class)->transition($order, 'release', $this->actor);
        }

        $batch = BagProductionBatch::query()->where('notes', $batchMarker)->first();
        if (! $batch) {
            $batch = app(BagBatchPersistenceService::class)->create([
                'bag_production_order_id' => $order->id,
                'production_date' => $today,
                'machine_id' => $masters['machines']['bag']->id,
                'production_hall_id' => $masters['halls']['bag']->id,
                'good_quantity' => $qty['good'],
                'rejected_quantity' => $qty['rejected'],
                'dana_consumed_kg' => $qty['dana'],
                'color_material_consumed_kg' => 0,
                'actual_weight_per_good_bag_g' => $qty['grams'],
                'reusable_waste_kg' => $qty['reusable'],
                'scrap_waste_kg' => $qty['scrap'],
                'process_loss_kg' => $qty['loss'],
                'reusable_waste_material_id' => $materials['reuse']->id,
                'scrap_waste_material_id' => $materials['scrap']->id,
                'notes' => $batchMarker,
                'operators' => [
                    ['operator_name' => $operator->name, 'labor_cost' => 150, 'hours' => 8],
                ],
                'consumptions' => [
                    [
                        'material_id' => $materials['masterbatch']->id,
                        'consumption_type' => 'color',
                        'quantity' => $qty['color'],
                        'unit' => 'kg',
                    ],
                ],
            ], $this->actor);
        }

        $this->advanceBatch($batch, BagBatchWorkflowService::class, BagBatchPostingService::class);
        $this->assignEmployee($operator, 'bag_batch', [
            'bag_production_batch_id' => $batch->fresh()->id,
            'bag_production_order_id' => $order->id,
            'machine_id' => $masters['machines']['bag']->id,
            'notes' => $batchMarker.'-ASG',
        ], $today);
    }

    /**
     * @param  array<string, mixed>  $masters
     * @param  array<string, mixed>  $people
     * @param  array<string, array{product: BagProduct, variant: BagProductVariant}>  $bags
     */
    private function sales(array $masters, array $people, array $bags, string $today): void
    {
        $this->saleFromOrder(
            'DEMO-SO-PLAIN',
            'DEMO-SI-PLAIN',
            'DEMO-RCT-PLAIN',
            $people['customers']['retail'],
            $masters['warehouses']['fg'],
            $bags['plain']['variant'],
            $today,
            120,
            25,
            100,
            5,
            true,
        );
        $this->directSale(
            'DEMO-SI-PRINT',
            $people['customers']['trade'],
            $masters['warehouses']['fg'],
            $bags['printed']['variant'],
            $today,
            40,
            40,
        );
    }

    private function saleFromOrder(
        string $orderMarker,
        string $invoiceMarker,
        string $receiptMarker,
        Customer $customer,
        Warehouse $warehouse,
        BagProductVariant $variant,
        string $date,
        float $qty,
        float $price,
        float $discount,
        float $tax,
        bool $partialPay,
    ): void {
        DB::transaction(function () use ($orderMarker, $invoiceMarker, $receiptMarker, $customer, $warehouse, $variant, $date, $qty, $price, $discount, $tax, $partialPay) {
            $order = SalesOrder::query()->where('notes', $orderMarker)->first();
            if (! $order) {
                $order = app(SalesOrderService::class)->createDraft([
                    'customer_id' => $customer->id,
                    'warehouse_id' => $warehouse->id,
                    'order_date' => $date,
                    'notes' => $orderMarker,
                ], [[
                    'bag_product_variant_id' => $variant->id,
                    'ordered_qty' => $qty,
                    'unit_price' => $price,
                    'discount_amount' => $discount,
                    'tax_rate' => $tax,
                ]], $this->actor);
            }
            if ($order->status === 'draft') {
                $order = app(SalesOrderService::class)->confirm($order, $this->actor);
            }

            $invoice = SalesInvoice::query()->where('notes', $invoiceMarker)->first();
            if (! $invoice) {
                $order->load('lines');
                $invoice = app(SalesInvoiceService::class)->createFromSalesOrder($order, [
                    'invoice_date' => $date,
                    'sale_type' => 'credit',
                    'notes' => $invoiceMarker,
                ], $order->lines->map(fn ($line) => [
                    'sales_order_line_id' => $line->id,
                    'quantity' => (float) $line->ordered_qty,
                ])->all(), $this->actor);
            }
            if ($invoice->status === 'draft') {
                $invoice = app(SalesInvoiceService::class)->post($invoice, $this->actor);
            }

            if ($partialPay && ! CustomerReceipt::query()->where('notes', $receiptMarker)->exists()) {
                $invoice = $invoice->fresh();
                $amount = round(((float) $invoice->grand_total) / 2, 2);
                if ($amount > 0) {
                    app(CustomerReceiptService::class)->record($customer->fresh(), [
                        'receipt_date' => $date,
                        'amount' => $amount,
                        'payment_method' => 'cash',
                        'notes' => $receiptMarker,
                        'reference' => $receiptMarker,
                    ], [
                        ['sales_invoice_id' => $invoice->id, 'amount' => $amount],
                    ], $this->actor);
                }
            }
        });
    }

    private function directSale(string $marker, Customer $customer, Warehouse $warehouse, BagProductVariant $variant, string $date, float $qty, float $price): void
    {
        if (SalesInvoice::query()->where('notes', $marker)->exists()) {
            $invoice = SalesInvoice::query()->where('notes', $marker)->first();
            if ($invoice && $invoice->status === 'draft') {
                app(SalesInvoiceService::class)->post($invoice, $this->actor);
            }

            return;
        }

        $invoice = app(SalesInvoiceService::class)->createDraft([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'invoice_date' => $date,
            'sale_type' => 'credit',
            'notes' => $marker,
        ], [[
            'bag_product_variant_id' => $variant->id,
            'quantity' => $qty,
            'unit_price' => $price,
            'discount_amount' => 0,
            'tax_rate' => 0,
        ]], $this->actor);
        app(SalesInvoiceService::class)->post($invoice, $this->actor);
    }

    /**
     * @param  array<string, mixed>  $masters
     */
    private function expenses(array $masters, string $today): void
    {
        $rows = [
            'DEMO-EXP-ELEC' => ['DEMO-ELEC', 18500],
            'DEMO-EXP-RENT' => ['DEMO-RENT', 42000],
            'DEMO-EXP-TRN' => ['DEMO-TRN', 6500],
            'DEMO-EXP-MNT' => ['DEMO-MNT', 4800],
            'DEMO-EXP-UTL' => ['DEMO-UTL', 3200],
        ];

        foreach ($rows as $marker => [$categoryCode, $amount]) {
            if (Expense::query()->where('description', $marker)->exists()) {
                continue;
            }
            $category = $masters['expenseCategories'][$categoryCode] ?? null;
            if (! $category) {
                $this->notes[] = "Skipped {$marker}: expense category missing.";

                continue;
            }
            app(ExpenseService::class)->create([
                'expense_category_id' => $category->id,
                'expense_date' => $today,
                'amount' => $amount,
                'payment_status' => 'paid',
                'payment_method' => 'cash',
                'description' => $marker,
            ], $this->actor);
        }
    }

    /**
     * @param  array<string, mixed>  $people
     */
    private function attendanceAndPayroll(array $people, string $today): void
    {
        $dates = [];
        $cursor = now()->startOfMonth();
        $end = now()->startOfDay();
        while ($cursor->lte($end)) {
            if ($cursor->isWeekday() || $cursor->isSameDay($end)) {
                $dates[] = $cursor->toDateString();
            }
            $cursor->addDay();
        }
        $dates = array_values(array_unique($dates));
        $absentDate = collect($dates)->first(fn (string $date) => $date !== $today);

        foreach ($dates as $date) {
            $monthlyStatus = ($absentDate !== null && $date === $absentDate) ? 'absent' : 'present';
            $this->markAttendance($people['monthly'], $date, $monthlyStatus);
            $this->markAttendance($people['daily'], $date, 'present');
        }

        if (! OvertimeRecord::query()->where('notes', 'DEMO-OT-01')->exists()) {
            $overtime = app(OvertimeService::class)->record([
                'employee_id' => $people['monthly']->id,
                'overtime_date' => $today,
                'hours' => 2,
                'notes' => 'DEMO-OT-01',
            ], $this->actor);
            app(OvertimeService::class)->approve($overtime, $this->actor, 'approved');
        }

        if (! EmployeeAdvance::query()->where('notes', 'DEMO-ADV-01')->exists()) {
            app(AdvanceLoanService::class)->recordAdvance($people['monthly'], [
                'advance_date' => now()->startOfMonth()->toDateString(),
                'amount' => 2000,
                'reason' => 'DEMO sample advance',
                'per_payroll_deduction' => 500,
                'notes' => 'DEMO-ADV-01',
            ], $this->actor);
        }
        if (! EmployeeLoan::query()->where('notes', 'DEMO-LOAN-01')->exists()) {
            app(AdvanceLoanService::class)->recordLoan($people['monthly'], [
                'loan_date' => now()->startOfMonth()->toDateString(),
                'principal' => 10000,
                'installment_amount' => 1000,
                'installment_count' => 10,
                'notes' => 'DEMO-LOAN-01',
            ], $this->actor);
        }

        $this->payroll($people);
    }

    private function payroll(array $people): void
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $foreign = Employee::query()
            ->where('is_active', true)
            ->where('employee_code', 'not like', 'DEMO-%')
            ->exists();

        $run = PayrollRun::query()
            ->whereDate('period_start', $start->toDateString())
            ->whereDate('period_end', $end->toDateString())
            ->first();

        if ($run && ! str_starts_with((string) $run->notes, 'DEMO-')) {
            $this->notes[] = 'Skipped DEMO payroll: a non-DEMO payroll run already owns '.$start->format('M Y').'.';

            return;
        }

        if (! $run && $foreign) {
            $this->notes[] = 'Skipped DEMO payroll: active employees outside DEMO- codes exist, and payroll generation includes every active employee.';

            return;
        }

        if (! $run) {
            $run = app(PayrollRunService::class)->createRun($start, $end, $this->actor, 'DEMO-PAYROLL');
        }

        if ($run->records()->count() === 0) {
            $run = app(PayrollRunService::class)->generate($run, $this->actor);
        }
        if ($run->status === 'draft') {
            $run = app(PayrollRunService::class)->review($run, $this->actor);
        }
        if (in_array($run->status, ['draft', 'reviewed'], true)) {
            $run = app(PayrollRunService::class)->approve($run, $this->actor);
        }

        if (PayrollPayment::query()->where('reference', 'DEMO-PAY-01')->exists()) {
            return;
        }

        $record = $run->fresh('records')->records->firstWhere('employee_id', $people['monthly']->id);
        if (! $record) {
            $this->notes[] = 'Skipped DEMO payroll payment: monthly DEMO record was not on the run.';

            return;
        }
        $outstanding = (float) $record->outstanding_amount;
        $amount = round(min(10000, $outstanding), 2);
        if ($amount <= 0) {
            return;
        }
        app(PayrollPaymentService::class)->recordPayment($record, [
            'payment_date' => now()->toDateString(),
            'amount' => $amount,
            'payment_method' => 'cash',
            'reference' => 'DEMO-PAY-01',
            'notes' => 'DEMO-PAY-01',
        ], $this->actor);
    }

    /**
     * @param  array<string, array{product: BagProduct, variant: BagProductVariant}>  $bags
     */
    private function publishCatalog(array $bags, Material $dana): void
    {
        if (! Schema::hasColumn('bag_products', 'is_public') || ! Schema::hasColumn('materials', 'is_public')) {
            $this->notes[] = 'Skipped public catalog flags: is_public columns are missing.';

            return;
        }

        $images = [
            'DEMO-BAG-PLAIN' => [29, 78, 216],
            'DEMO-BAG-PRINT' => [15, 118, 110],
            'DEMO-BAG-CUSTOM' => [180, 83, 9],
        ];
        foreach ($bags as $bag) {
            $product = $bag['product']->fresh();
            $rgb = $images[$product->code] ?? [15, 39, 68];
            $path = $this->placeholder('website/listings/'.$product->code.'.png', $rgb[0], $rgb[1], $rgb[2]);
            if ($product->public_image_path !== $path) {
                $product->update(['public_image_path' => $path, 'is_public' => true]);
            }
        }

        $danaCopy = 'Sample catalog text. This dana listing is a demo catalog row. Stock and cost are not shown.';
        $path = $this->placeholder('website/listings/DEMO-DAN-NAT.png', 100, 116, 139);
        $dana->update([
            'is_public' => true,
            'public_slug' => 'demo-natural-dana',
            'public_category' => 'dana-granules',
            'public_summary' => $danaCopy,
            'public_description' => $danaCopy,
            'public_image_path' => $path,
            'public_sort' => 1,
        ]);
    }

    private function placeholder(string $path, int $red, int $green, int $blue): string
    {
        if (! Storage::disk('local')->exists($path)) {
            Storage::disk('local')->put($path, $this->png($red, $green, $blue));
        }

        return $path;
    }

    private function png(int $red, int $green, int $blue): string
    {
        if (function_exists('imagecreatetruecolor')) {
            $image = imagecreatetruecolor(640, 400);
            $color = imagecolorallocate($image, $red, $green, $blue);
            imagefilledrectangle($image, 0, 0, 640, 400, $color);
            $ink = imagecolorallocate($image, 255, 255, 255);
            imagestring($image, 5, 24, 24, 'DEMO sample', $ink);
            ob_start();
            imagepng($image);
            $bytes = ob_get_clean();
            imagedestroy($image);

            return is_string($bytes) ? $bytes : '';
        }

        $raw = str_repeat(chr($red).chr($green).chr($blue), 8);
        $scan = chr(0).$raw;
        $compressed = gzcompress($scan);
        $chunk = function (string $type, string $data): string {
            return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        };

        return "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', 8, 1, 8, 2, 0, 0, 0))
            .$chunk('IDAT', $compressed)
            .$chunk('IEND', '');
    }

    private function report(): void
    {
        $counts = [
            'customers' => Customer::query()->where('code', 'like', 'DEMO-%')->count(),
            'suppliers' => Supplier::query()->where('code', 'like', 'DEMO-%')->count(),
            'employees' => Employee::query()->where('employee_code', 'like', 'DEMO-%')->count(),
            'materials' => Material::query()->where('code', 'like', 'DEMO-%')->count(),
            'public_bags' => BagProduct::query()->where('code', 'like', 'DEMO-%')->where('is_public', true)->count(),
            'public_materials' => Material::query()->where('code', 'like', 'DEMO-%')->where('is_public', true)->count(),
            'dana_batches' => DanaBatch::query()->where('notes', 'like', 'DEMO-DANA-%')->count(),
            'bag_batches' => BagProductionBatch::query()->where('notes', 'like', 'DEMO-BBAT-%')->count(),
            'purchase_orders' => PurchaseOrder::query()->where('notes', 'like', 'DEMO-PO-%')->count(),
            'sales_invoices' => SalesInvoice::query()->where('notes', 'like', 'DEMO-SI-%')->count(),
            'expenses' => Expense::query()->where('description', 'like', 'DEMO-EXP-%')->count(),
            'payroll_runs' => PayrollRun::query()->where('notes', 'like', 'DEMO-%')->count(),
        ];

        foreach ($counts as $label => $count) {
            $this->command?->info(sprintf('DEMO %s: %d', $label, $count));
        }
        foreach ($this->notes as $note) {
            $this->command?->warn($note);
        }
    }

    private function master(string $class, string $code, string $name): mixed
    {
        return $class::query()->updateOrCreate(
            ['code' => $code],
            ['name' => $name, 'status' => 'active', 'description' => 'DEMO sample master row.'],
        );
    }

    private function color(string $code, string $name, string $hex): Color
    {
        return Color::query()->updateOrCreate(
            ['code' => $code],
            ['name' => $name, 'hex_color' => $hex, 'status' => 'active', 'description' => 'DEMO sample color.'],
        );
    }

    private function warehouse(string $code, string $name): Warehouse
    {
        return Warehouse::query()->updateOrCreate(
            ['code' => $code],
            ['name' => $name, 'address' => null, 'description' => 'DEMO sample warehouse. Address left blank.', 'status' => 'active'],
        );
    }

    private function supplier(string $code, string $name): Supplier
    {
        $existing = Supplier::query()->where('code', $code)->first();
        if ($existing) {
            return $existing;
        }

        return app(SupplierPersistenceService::class)->create([
            'code' => $code,
            'name' => $name,
            'opening_balance' => 0,
            'status' => 'active',
            'notes' => 'DEMO sample supplier. Phone and address left blank.',
        ], $this->actor);
    }

    private function customer(string $code, string $name): Customer
    {
        $existing = Customer::query()->where('code', $code)->first();
        if ($existing) {
            return $existing;
        }

        return app(CustomerPersistenceService::class)->create([
            'code' => $code,
            'name' => $name,
            'opening_balance' => 0,
            'status' => 'active',
            'notes' => 'DEMO sample customer. Phone and address left blank.',
        ], $this->actor);
    }

    /**
     * @param  array<string, mixed>  $masters
     * @param  array<string, mixed>  $pay
     */
    private function employee(string $code, string $name, string $salaryType, array $masters, array $pay): Employee
    {
        $existing = Employee::query()->where('employee_code', $code)->first();
        if ($existing) {
            return $existing;
        }

        return app(EmployeePersistenceService::class)->create([
            'employee_code' => $code,
            'name' => $name,
            'phone' => null,
            'address' => null,
            'joining_date' => now()->startOfMonth()->subMonth()->toDateString(),
            'department_id' => $masters['department']->id,
            'designation_id' => $pay['designation_id'],
            'salary_type' => $salaryType,
            'basic_monthly_salary' => $pay['basic_monthly_salary'],
            'daily_wage_rate' => $pay['daily_wage_rate'],
            'overtime_rate' => $pay['overtime_rate'],
            'status' => 'active',
            'notes' => 'DEMO sample employee. Phone and address left blank.',
        ], $this->actor);
    }

    private function markAttendance(Employee $employee, string $date, string $status): void
    {
        if (Attendance::query()->where('employee_id', $employee->id)->whereDate('attendance_date', $date)->exists()) {
            return;
        }

        app(AttendanceService::class)->record([
            'employee_id' => $employee->id,
            'attendance_date' => $date,
            'status' => $status,
            'check_in_at' => $status === 'present' ? '08:00' : null,
            'check_out_at' => $status === 'present' ? '17:00' : null,
            'notes' => 'DEMO attendance',
        ], $this->actor);
    }

    /**
     * @param  array<string, mixed>  $target
     */
    private function assignEmployee(Employee $employee, string $type, array $target, string $date): void
    {
        $notes = (string) ($target['notes'] ?? '');
        if ($notes !== '' && EmployeeProductionAssignment::query()->where('notes', $notes)->exists()) {
            return;
        }

        app(EmployeeProductionAssignmentService::class)->create([
            'employee_id' => $employee->id,
            'assignment_type' => $type,
            'assigned_from' => $date,
            'assigned_to' => $date,
            ...$target,
        ], $this->actor);
    }

    /**
     * @param  class-string  $workflow
     * @param  class-string  $posting
     */
    private function advanceBatch(DanaBatch|BagProductionBatch $batch, string $workflow, string $posting): void
    {
        $flow = app($workflow);
        foreach (['submit', 'approve', 'start', 'complete'] as $action) {
            $batch = $batch->fresh();
            if (in_array($batch->status, ['completed', 'posted'], true)) {
                break;
            }
            if (in_array($action, $flow->allowedActions($batch), true)) {
                $batch = $flow->transition($batch, $action, $this->actor);
            }
        }

        $batch = $batch->fresh();
        if ($batch->status === 'completed') {
            app($posting)->post($batch, $this->actor);
        }
    }

}
