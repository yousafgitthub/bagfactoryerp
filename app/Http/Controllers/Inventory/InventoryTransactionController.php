<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\InventoryTransactionRequest;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\Material;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryPostingException;
use App\Services\Inventory\InventoryPostingService;
use App\Services\Inventory\InventoryTransactionNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InventoryTransactionController extends Controller
{
    public function __construct(
        private readonly InventoryPostingService $posting,
        private readonly InventoryTransactionNumberGenerator $numbers,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'inventory');

        $query = InventoryTransaction::query()
            ->with(['warehouse', 'creator'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        if ($type = $request->string('type')->trim()->toString()) {
            $query->where('type', $type);
        }

        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }

        $transactions = $query->paginate(20)->withQueryString();

        return Inertia::render('Inventory/Transactions/Index', [
            'pageTitle' => 'Stock movements',
            'breadcrumbs' => $this->breadcrumbs('Stock movements'),
            'transactions' => $transactions,
            'filters' => ['type' => $type, 'status' => $status],
            'transactionTypes' => config('inventory.transaction_types'),
            'canManage' => $request->user()?->can('inventory.manage') === true,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('manageInventory');

        $type = $request->string('type', 'purchase_receipt')->toString();
        if (! array_key_exists($type, config('inventory.transaction_types'))) {
            $type = 'purchase_receipt';
        }

        return Inertia::render('Inventory/Transactions/Form', [
            'pageTitle' => 'New stock movement',
            'breadcrumbs' => $this->breadcrumbs('New movement'),
            'type' => $type,
            'transactionTypes' => config('inventory.transaction_types'),
            'lookups' => $this->lookups(),
        ]);
    }

    public function store(InventoryTransactionRequest $request): RedirectResponse
    {
        $this->authorize('manageInventory');

        $data = $request->validated();
        $type = $data['type'];

        $transaction = DB::transaction(function () use ($request, $data, $type) {
            $transaction = InventoryTransaction::query()->create([
                'transaction_number' => $this->numbers->next($type),
                'type' => $type,
                'status' => 'draft',
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'from_warehouse_id' => $data['from_warehouse_id'] ?? null,
                'to_warehouse_id' => $data['to_warehouse_id'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            if ($type === 'warehouse_transfer') {
                $transfer = StockTransfer::query()->create([
                    'inventory_transaction_id' => $transaction->id,
                    'from_warehouse_id' => $data['from_warehouse_id'],
                    'to_warehouse_id' => $data['to_warehouse_id'],
                ]);

                foreach ($data['items'] as $index => $line) {
                    StockTransferItem::query()->create([
                        'stock_transfer_id' => $transfer->id,
                        'material_id' => $line['material_id'],
                        'material_variant_id' => $line['material_variant_id'] ?? null,
                        'quantity' => $line['quantity'],
                        'unit_cost' => $line['unit_cost'] ?? 0,
                    ]);
                }
            }

            if ($type === 'adjustment') {
                StockAdjustment::query()->create([
                    'inventory_transaction_id' => $transaction->id,
                    'reason' => $data['adjustment_reason'],
                    'direction' => $data['adjustment_direction'],
                ]);
            }

            if ($type !== 'warehouse_transfer') {
                foreach ($data['items'] as $index => $line) {
                    $qty = (float) $line['quantity'];
                    $unitCost = (float) ($line['unit_cost'] ?? 0);

                    InventoryTransactionItem::query()->create([
                        'inventory_transaction_id' => $transaction->id,
                        'material_id' => $line['material_id'],
                        'material_variant_id' => $line['material_variant_id'] ?? null,
                        'quantity' => $qty,
                        'unit_cost' => $unitCost,
                        'total_value' => round($qty * $unitCost, 4),
                        'line_order' => $index + 1,
                    ]);
                }
            }

            return $transaction;
        });

        return redirect()
            ->route('inventory.transactions.show', $transaction)
            ->with('success', 'Draft saved. Post when ready.');
    }

    public function show(InventoryTransaction $transaction): Response
    {
        $this->authorize('viewModule', 'inventory');

        $transaction->load([
            'items.material.unit',
            'items.variant',
            'warehouse',
            'fromWarehouse',
            'toWarehouse',
            'stockAdjustment',
            'stockTransfer.items.material',
            'ledgerEntries',
            'creator',
            'poster',
        ]);

        return Inertia::render('Inventory/Transactions/Show', [
            'pageTitle' => $transaction->transaction_number,
            'breadcrumbs' => $this->breadcrumbs($transaction->transaction_number),
            'transaction' => $transaction,
            'transactionTypes' => config('inventory.transaction_types'),
            'canManage' => auth()->user()?->can('inventory.manage') === true,
        ]);
    }

    public function post(Request $request, InventoryTransaction $transaction): RedirectResponse
    {
        $this->authorize('manageInventory');

        try {
            $this->posting->post($transaction, $request->user());
        } catch (InventoryPostingException $exception) {
            return back()->withErrors(['post' => $exception->getMessage()]);
        }

        return back()->with('success', 'Transaction posted.');
    }

    public function reverse(Request $request, InventoryTransaction $transaction): RedirectResponse
    {
        $this->authorize('manageInventory');

        if (! $transaction->isPosted()) {
            return back()->withErrors(['reverse' => 'Only posted transactions can be reversed.']);
        }

        if ($transaction->type === 'reversal') {
            return back()->withErrors(['reverse' => 'Cannot reverse a reversal.']);
        }

        $reversal = DB::transaction(function () use ($request, $transaction) {
            return InventoryTransaction::query()->create([
                'transaction_number' => $this->numbers->next('reversal'),
                'type' => 'reversal',
                'status' => 'draft',
                'warehouse_id' => $transaction->warehouse_id,
                'transaction_date' => now()->toDateString(),
                'notes' => 'Reversal of '.$transaction->transaction_number,
                'created_by' => $request->user()->id,
                'reversal_of_id' => $transaction->id,
            ]);
        });

        try {
            $this->posting->post($reversal, $request->user());
        } catch (InventoryPostingException $exception) {
            return back()->withErrors(['reverse' => $exception->getMessage()]);
        }

        return redirect()
            ->route('inventory.transactions.show', $reversal)
            ->with('success', 'Reversal posted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function lookups(): array
    {
        return [
            'warehouses' => Warehouse::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']),
            'materials' => Material::query()->where('status', 'active')->with('variants')->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<int, array{label: string, href?: string}>
     */
    private function breadcrumbs(string $leaf): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Inventory', 'href' => route('erp.inventory')],
            ['label' => $leaf],
        ];
    }
}