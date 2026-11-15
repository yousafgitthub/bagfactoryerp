<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;

class InventoryReconciliationService
{
    /**
     * @return list<array{warehouse_id: int, material_id: int, material_variant_id: int|null, balance_qty: float, ledger_qty: float, delta: float}>
     */
    public function compareBalancesToLedger(): array
    {
        $ledger = DB::table('inventory_ledger_entries')
            ->select('warehouse_id', 'material_id', 'material_variant_id')
            ->selectRaw('SUM(quantity) as ledger_qty')
            ->groupBy('warehouse_id', 'material_id', 'material_variant_id')
            ->get()
            ->keyBy(fn ($row) => $this->key((int) $row->warehouse_id, (int) $row->material_id, $row->material_variant_id));

        $balances = DB::table('inventory_balances')->get();
        $rows = [];

        foreach ($balances as $balance) {
            $key = $this->key((int) $balance->warehouse_id, (int) $balance->material_id, $balance->material_variant_id);
            $ledgerQty = (float) ($ledger[$key]->ledger_qty ?? 0);
            $balanceQty = (float) $balance->quantity;
            $delta = round($balanceQty - $ledgerQty, 4);

            if (abs($delta) > 0.0001) {
                $rows[] = [
                    'warehouse_id' => (int) $balance->warehouse_id,
                    'material_id' => (int) $balance->material_id,
                    'material_variant_id' => $balance->material_variant_id !== null ? (int) $balance->material_variant_id : null,
                    'balance_qty' => $balanceQty,
                    'ledger_qty' => $ledgerQty,
                    'delta' => $delta,
                ];
            }
        }

        foreach ($ledger as $key => $row) {
            if ($balances->first(fn ($b) => $this->key((int) $b->warehouse_id, (int) $b->material_id, $b->material_variant_id) === $key)) {
                continue;
            }

            $ledgerQty = (float) $row->ledger_qty;
            if (abs($ledgerQty) < 0.0001) {
                continue;
            }

            $rows[] = [
                'warehouse_id' => (int) $row->warehouse_id,
                'material_id' => (int) $row->material_id,
                'material_variant_id' => $row->material_variant_id !== null ? (int) $row->material_variant_id : null,
                'balance_qty' => 0.0,
                'ledger_qty' => $ledgerQty,
                'delta' => -$ledgerQty,
            ];
        }

        return $rows;
    }

    private function key(int $warehouseId, int $materialId, mixed $variantId): string
    {
        return $warehouseId.':'. $materialId.':'.($variantId ?? 'null');
    }
}