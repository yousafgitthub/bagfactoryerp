<?php

namespace App\Services\Sales;

use App\Models\BagProductVariant;
use App\Models\InventoryLedgerEntry;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnLine;
use App\Models\User;
use App\Services\Accounting\AccountingIntegrationService;
use App\Services\Inventory\InventoryPostingService;
use App\Services\Inventory\InventoryTransactionNumberGenerator;
use App\Services\Purchases\PurchaseLineCalculator;
use Illuminate\Support\Facades\DB;

class SalesReturnService
{
    public function __construct(
        private readonly SalesDocumentNumberGenerator $numbers,
        private readonly PurchaseLineCalculator $calculator,
        private readonly InventoryTransactionNumberGenerator $inventoryNumbers,
        private readonly InventoryPostingService $inventoryPosting,
        private readonly CustomerLedgerService $ledger,
        private readonly AccountingIntegrationService $accounting,
    ) {
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    public function createDraft(array $header, array $lines, User $actor): SalesReturn
    {
        return DB::transaction(function () use ($header, $lines, $actor) {
            $document = SalesReturn::query()->create([
                'return_number' => $this->numbers->next('sales_return'),
                'customer_id' => $header['customer_id'],
                'sales_invoice_id' => $header['sales_invoice_id'] ?? null,
                'warehouse_id' => $header['warehouse_id'],
                'return_date' => $header['return_date'],
                'status' => 'draft',
                'notes' => $header['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $subtotal = 0.0;
            $taxTotal = 0.0;

            foreach ($lines as $row) {
                $qty = (float) $row['quantity'];
                $unitPrice = (float) $row['unit_price'];
                $taxRate = (float) ($row['tax_rate'] ?? 0);
                $calc = $this->calculator->compute($qty, $unitPrice, 0, $taxRate);
                $subtotal += $calc['net_before_tax'];
                $taxTotal += $calc['tax_amount'];

                SalesReturnLine::query()->create([
                    'sales_return_id' => $document->id,
                    'sales_invoice_line_id' => $row['sales_invoice_line_id'] ?? null,
                    'bag_product_variant_id' => $row['bag_product_variant_id'] ?? null,
                    'material_id' => $row['material_id'],
                    'material_variant_id' => $row['material_variant_id'] ?? null,
                    'quantity' => $qty,
                    'condition' => $row['condition'],
                    'disposition' => null,
                    'unit_price' => $unitPrice,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $calc['tax_amount'],
                    'line_total' => $calc['line_total'],
                    'inspection_notes' => $row['inspection_notes'] ?? null,
                ]);
            }

            $document->update([
                'subtotal' => round($subtotal, 2),
                'tax_total' => round($taxTotal, 2),
                'grand_total' => round($subtotal + $taxTotal, 2),
            ]);

            return $document->fresh(['lines.material']);
        });
    }

    public function post(SalesReturn $salesReturn, User $actor): SalesReturn
    {
        if (! $salesReturn->isDraft()) {
            throw new SalesException('Only draft sales returns can be posted.');
        }

        return DB::transaction(function () use ($salesReturn, $actor) {
            $locked = SalesReturn::query()->whereKey($salesReturn->id)->lockForUpdate()->with('lines', 'customer')->firstOrFail();

            if ($locked->lines->isEmpty()) {
                throw new SalesException('Return has no lines.');
            }

            if ($locked->inventory_transaction_id) {
                throw new SalesException('Return already posted to inventory.');
            }

            $inventoryIds = [];
            $saleable = $locked->lines->filter(fn ($line) => $line->condition === 'saleable');
            $rejected = $locked->lines->reject(fn ($line) => $line->condition === 'saleable');

            if ($saleable->isNotEmpty()) {
                $inventoryIds[] = $this->postInventoryGroup(
                    $locked,
                    $saleable,
                    'sales_return_saleable',
                    'saleable_stock',
                    $actor,
                );
            }

            if ($rejected->isNotEmpty()) {
                $inventoryIds[] = $this->postInventoryGroup(
                    $locked,
                    $rejected,
                    'sales_return_rejected',
                    'rejected_stock',
                    $actor,
                );
            }

            $locked->update([
                'status' => 'posted',
                'inventory_transaction_id' => $inventoryIds[0] ?? null,
                'posted_at' => now(),
                'posted_by' => $actor->id,
            ]);

            $this->ledger->recordReturn(
                customer: $locked->customer,
                entryDate: $locked->return_date->toDateString(),
                returnId: $locked->id,
                returnNumber: $locked->return_number,
                amount: (float) $locked->grand_total,
                actor: $actor,
            );

            $cogs = 0.0;
            if ($inventoryIds !== []) {
                $cogs = (float) InventoryLedgerEntry::query()
                    ->whereIn('inventory_transaction_id', $inventoryIds)
                    ->where('quantity', '>', 0)
                    ->sum('total_value');
            }

            $this->accounting->postSalesReturn($locked->fresh(), (float) $locked->grand_total, $cogs, $actor);

            return $locked->fresh(['lines', 'inventoryTransaction']);
        });
    }

  /**
   * @param  iterable<int, SalesReturnLine>  $lines
   */
    private function postInventoryGroup(
        SalesReturn $document,
        iterable $lines,
        string $type,
        string $disposition,
        User $actor,
    ): int {
        $inventory = InventoryTransaction::query()->create([
            'transaction_number' => $this->inventoryNumbers->next($type),
            'type' => $type,
            'status' => 'draft',
            'warehouse_id' => $document->warehouse_id,
            'transaction_date' => $document->return_date,
            'reference_type' => SalesReturn::class,
            'reference_id' => $document->id,
            'notes' => "Sales return {$document->return_number}",
            'created_by' => $actor->id,
        ]);

        foreach ($lines as $line) {
            $line->update(['disposition' => $disposition]);

            $materialId = (int) $line->material_id;
            $variantId = $line->material_variant_id;

            if ($disposition === 'rejected_stock') {
                $variant = $line->bag_product_variant_id
                    ? BagProductVariant::query()->find($line->bag_product_variant_id)
                    : null;
                $materialId = (int) ($variant?->rejected_material_id ?? $materialId);
                $variantId = $variant?->rejected_material_variant_id ?? $variantId;
            }

            InventoryTransactionItem::query()->create([
                'inventory_transaction_id' => $inventory->id,
                'material_id' => $materialId,
                'material_variant_id' => $variantId,
                'quantity' => $line->quantity,
                'unit_cost' => 0,
            ]);
        }

        $this->inventoryPosting->post($inventory, $actor);

        return $inventory->id;
    }
}