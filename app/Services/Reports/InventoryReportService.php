<?php

namespace App\Services\Reports;

use App\Models\InventoryBalance;
use App\Models\Material;
use App\Services\Reports\Concerns\PaginatesReportRows;
use Illuminate\Support\Facades\DB;

class InventoryReportService
{
    use PaginatesReportRows;

    public function currentStock(ReportFilters $filters): ReportResult
    {
        return $this->stockByBucket(null, $filters, 'Current stock', 'All buckets from inventory balances (ledger-aligned).');
    }

    public function rawMaterialStock(ReportFilters $filters): ReportResult
    {
        return $this->stockByBucket('raw', $filters, 'Raw material stock', 'Materials with inventory bucket raw.');
    }

    public function danaStock(ReportFilters $filters): ReportResult
    {
        return $this->stockByBucket('dana', $filters, 'Dana stock', 'Finished Dana inventory balances.');
    }

    public function finishedBagStock(ReportFilters $filters): ReportResult
    {
        return $this->stockByBucket('finished_bag', $filters, 'Finished bag stock', 'Saleable finished goods stock.');
    }

    public function variantStock(ReportFilters $filters): ReportResult
    {
        $query = DB::table('inventory_balances as b')
            ->join('materials as m', 'm.id', '=', 'b.material_id')
            ->leftJoin('material_variants as v', 'v.id', '=', 'b.material_variant_id')
            ->leftJoin('colors as c', 'c.id', '=', 'v.color_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'b.warehouse_id')
            ->when($filters->warehouseId, fn ($q) => $q->where('b.warehouse_id', $filters->warehouseId))
            ->selectRaw('m.code as material_code, m.name as material_name')
            ->selectRaw("COALESCE(v.variant_code, '—') as variant_code")
            ->selectRaw("COALESCE(c.name, v.name, '—') as color")
            ->selectRaw('w.name as warehouse')
            ->selectRaw('b.quantity, b.average_unit_cost, b.total_value')
            ->orderBy('m.code');

        $all = $query->get()->map(fn ($r) => [
            'material' => $r->material_code.' '.$r->material_name,
            'variant' => $r->variant_code,
            'color' => $r->color,
            'warehouse' => $r->warehouse,
            'quantity' => round((float) $r->quantity, 4),
            'unit_cost' => round((float) $r->average_unit_cost, 4),
            'value' => round((float) $r->total_value, 2),
        ])->all();

        return new ReportResult(
            title: 'Color / variant-wise stock',
            description: 'Balances including material variant and color.',
            columns: [
                ['key' => 'material', 'label' => 'Material', 'sortable' => true],
                ['key' => 'variant', 'label' => 'Variant', 'sortable' => true],
                ['key' => 'color', 'label' => 'Color', 'sortable' => true],
                ['key' => 'warehouse', 'label' => 'Warehouse', 'sortable' => true],
                ['key' => 'quantity', 'label' => 'Qty', 'sortable' => true],
                ['key' => 'unit_cost', 'label' => 'WAC', 'sortable' => true],
                ['key' => 'value', 'label' => 'Value', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            summary: ['total_value' => round(array_sum(array_column($all, 'value')), 2)],
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function stockLedger(ReportFilters $filters): ReportResult
    {
        $query = DB::table('inventory_ledger_entries as l')
            ->join('materials as m', 'm.id', '=', 'l.material_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'l.warehouse_id')
            ->when($filters->from, fn ($q) => $q->whereDate('l.occurred_at', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('l.occurred_at', '<=', $filters->to))
            ->when($filters->warehouseId, fn ($q) => $q->where('l.warehouse_id', $filters->warehouseId))
            ->selectRaw('l.occurred_at, l.movement_type, m.code, m.name, w.name as warehouse')
            ->selectRaw('l.quantity, l.unit_cost, l.total_value, l.reference')
            ->orderByDesc('l.occurred_at')
            ->limit(2000);

        $all = $query->get()->map(fn ($r) => [
            'date' => (string) $r->occurred_at,
            'type' => $r->movement_type,
            'material' => $r->code.' '.$r->name,
            'warehouse' => $r->warehouse,
            'quantity' => round((float) $r->quantity, 4),
            'unit_cost' => round((float) $r->unit_cost, 4),
            'value' => round((float) $r->total_value, 2),
            'notes' => $r->reference ?? '',
        ])->all();

        return new ReportResult(
            title: 'Stock ledger',
            description: 'Posted inventory ledger movements (source for stock reconciliation).',
            columns: [
                ['key' => 'date', 'label' => 'Date', 'sortable' => true],
                ['key' => 'type', 'label' => 'Type', 'sortable' => true],
                ['key' => 'material', 'label' => 'Material', 'sortable' => true],
                ['key' => 'warehouse', 'label' => 'Warehouse', 'sortable' => true],
                ['key' => 'quantity', 'label' => 'Qty', 'sortable' => true],
                ['key' => 'unit_cost', 'label' => 'Unit cost', 'sortable' => true],
                ['key' => 'value', 'label' => 'Value', 'sortable' => true],
                ['key' => 'notes', 'label' => 'Notes', 'sortable' => false],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function materialConsumption(ReportFilters $filters): ReportResult
    {
        $dana = DB::table('dana_batch_material_inputs as i')
            ->join('dana_batches as b', 'b.id', '=', 'i.dana_batch_id')
            ->join('materials as m', 'm.id', '=', 'i.material_id')
            ->where('b.status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('b.production_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('b.production_date', '<=', $filters->to))
            ->selectRaw('m.code, m.name')
            ->selectRaw('SUM(i.quantity_kg) as qty_kg')
            ->selectRaw('SUM(i.quantity_kg * i.unit_cost_snapshot) as cost')
            ->groupBy('m.id', 'm.code', 'm.name');

        $bag = DB::table('bag_batch_material_consumptions as i')
            ->join('bag_production_batches as b', 'b.id', '=', 'i.bag_production_batch_id')
            ->join('materials as m', 'm.id', '=', 'i.material_id')
            ->where('b.status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('b.production_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('b.production_date', '<=', $filters->to))
            ->selectRaw('m.code, m.name')
            ->selectRaw('SUM(i.quantity) as qty')
            ->selectRaw('SUM(i.quantity * i.unit_cost_snapshot) as cost')
            ->groupBy('m.id', 'm.code', 'm.name')
            ->get()
            ->keyBy(fn ($r) => $r->code);

        $all = [];
        foreach ($dana->get() as $row) {
            $bagRow = $bag[$row->code] ?? null;
            $all[] = [
                'material' => $row->code.' '.$row->name,
                'dana_kg' => round((float) $row->qty_kg, 2),
                'bag_qty' => $bagRow ? round((float) $bagRow->qty, 2) : 0,
                'total_cost' => round((float) $row->cost + (float) ($bagRow->cost ?? 0), 2),
            ];
        }

        foreach ($bag as $code => $row) {
            if (collect($all)->contains(fn ($a) => str_starts_with($a['material'], $code.' '))) {
                continue;
            }
            $all[] = [
                'material' => $row->code.' '.$row->name,
                'dana_kg' => 0,
                'bag_qty' => round((float) $row->qty, 2),
                'total_cost' => round((float) $row->cost, 2),
            ];
        }

        return new ReportResult(
            title: 'Material consumption',
            description: 'Consumption from posted Dana and bag batches (not purchase duplicates).',
            columns: [
                ['key' => 'material', 'label' => 'Material', 'sortable' => true],
                ['key' => 'dana_kg', 'label' => 'Dana usage (kg)', 'sortable' => true],
                ['key' => 'bag_qty', 'label' => 'Bag usage (qty)', 'sortable' => true],
                ['key' => 'total_cost', 'label' => 'Extended cost', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function valuation(ReportFilters $filters): ReportResult
    {
        $totals = DB::table('inventory_balances')
            ->selectRaw('bucket')
            ->selectRaw('SUM(total_value) as value')
            ->selectRaw('SUM(quantity) as qty')
            ->groupBy('bucket')
            ->get();

        $all = $totals->map(fn ($r) => [
            'bucket' => config('inventory.inventory_buckets')[$r->bucket] ?? $r->bucket,
            'quantity' => round((float) $r->qty, 2),
            'value' => round((float) $r->value, 2),
        ])->all();

        return new ReportResult(
            title: 'Inventory valuation',
            description: 'WAC-based total_value from balance table (matches inventory ledger).',
            columns: [
                ['key' => 'bucket', 'label' => 'Bucket', 'sortable' => true],
                ['key' => 'quantity', 'label' => 'Quantity', 'sortable' => true],
                ['key' => 'value', 'label' => 'Total value', 'sortable' => true],
            ],
            rows: $all,
            filters: $filters->toArray(),
            summary: ['inventory_value' => round(array_sum(array_column($all, 'value')), 2)],
            totalRows: count($all),
        );
    }

    public function lowStock(ReportFilters $filters): ReportResult
    {
        $materials = Material::query()
            ->where('status', 'active')
            ->where('reorder_level', '>', 0)
            ->get(['id', 'code', 'name', 'reorder_level', 'inventory_bucket']);

        $balances = InventoryBalance::query()
            ->selectRaw('material_id, SUM(quantity) as qty')
            ->groupBy('material_id')
            ->pluck('qty', 'material_id');

        $all = [];
        foreach ($materials as $m) {
            $qty = (float) ($balances[$m->id] ?? 0);
            if ($qty <= (float) $m->reorder_level) {
                $all[] = [
                    'material' => $m->code.' '.$m->name,
                    'bucket' => $m->inventory_bucket,
                    'on_hand' => round($qty, 4),
                    'reorder_level' => round((float) $m->reorder_level, 4),
                    'shortfall' => round((float) $m->reorder_level - $qty, 4),
                ];
            }
        }

        return new ReportResult(
            title: 'Low stock',
            description: 'Materials at or below reorder level (on-hand from balances).',
            columns: [
                ['key' => 'material', 'label' => 'Material', 'sortable' => true],
                ['key' => 'bucket', 'label' => 'Bucket', 'sortable' => true],
                ['key' => 'on_hand', 'label' => 'On hand', 'sortable' => true],
                ['key' => 'reorder_level', 'label' => 'Reorder level', 'sortable' => true],
                ['key' => 'shortfall', 'label' => 'Shortfall', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function wasteAndScrap(ReportFilters $filters): ReportResult
    {
        $wasteLedgers = DB::table('inventory_ledger_entries as l')
            ->join('materials as m', 'm.id', '=', 'l.material_id')
            ->whereIn('l.movement_type', ['waste_receipt', 'issue_to_production'])
            ->where(function ($q) {
                $q->where('m.inventory_bucket', 'other')
                    ->orWhere('m.name', 'like', '%waste%')
                    ->orWhere('m.name', 'like', '%scrap%');
            })
            ->when($filters->from, fn ($q) => $q->whereDate('l.occurred_at', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('l.occurred_at', '<=', $filters->to))
            ->selectRaw('m.code, m.name, SUM(l.quantity) as qty, SUM(l.total_value) as value')
            ->groupBy('m.id', 'm.code', 'm.name')
            ->get();

        $batchWaste = DB::table('dana_batches')
            ->where('status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('production_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('production_date', '<=', $filters->to))
            ->selectRaw('SUM(reusable_waste_kg) as reusable, SUM(scrap_waste_kg) as scrap')
            ->first();

        $all = $wasteLedgers->map(fn ($r) => [
            'source' => 'Inventory ledger',
            'item' => $r->code.' '.$r->name,
            'quantity' => round((float) $r->qty, 4),
            'value' => round((float) $r->value, 2),
        ])->all();

        $all[] = [
            'source' => 'Dana batches (posted)',
            'item' => 'Reusable waste (kg)',
            'quantity' => round((float) ($batchWaste->reusable ?? 0), 2),
            'value' => '',
        ];
        $all[] = [
            'source' => 'Dana batches (posted)',
            'item' => 'Scrap waste (kg)',
            'quantity' => round((float) ($batchWaste->scrap ?? 0), 2),
            'value' => '',
        ];

        return new ReportResult(
            title: 'Reusable waste & scrap',
            description: 'Waste movements from inventory ledger and batch waste quantities.',
            columns: [
                ['key' => 'source', 'label' => 'Source', 'sortable' => true],
                ['key' => 'item', 'label' => 'Item', 'sortable' => true],
                ['key' => 'quantity', 'label' => 'Quantity', 'sortable' => true],
                ['key' => 'value', 'label' => 'Value', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    private function stockByBucket(?string $bucket, ReportFilters $filters, string $title, string $description): ReportResult
    {
        $query = DB::table('inventory_balances as b')
            ->join('materials as m', 'm.id', '=', 'b.material_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'b.warehouse_id')
            ->when($bucket, fn ($q) => $q->where('m.inventory_bucket', $bucket))
            ->when($filters->warehouseId, fn ($q) => $q->where('b.warehouse_id', $filters->warehouseId))
            ->selectRaw('m.code, m.name, m.inventory_bucket, w.name as warehouse')
            ->selectRaw('b.quantity, b.average_unit_cost, b.total_value')
            ->orderBy('m.code');

        $all = $query->get()->map(fn ($r) => [
            'material' => $r->code.' '.$r->name,
            'bucket' => $r->inventory_bucket,
            'warehouse' => $r->warehouse,
            'quantity' => round((float) $r->quantity, 4),
            'unit_cost' => round((float) $r->average_unit_cost, 4),
            'value' => round((float) $r->total_value, 2),
        ])->all();

        return new ReportResult(
            title: $title,
            description: $description,
            columns: [
                ['key' => 'material', 'label' => 'Material', 'sortable' => true],
                ['key' => 'bucket', 'label' => 'Bucket', 'sortable' => true],
                ['key' => 'warehouse', 'label' => 'Warehouse', 'sortable' => true],
                ['key' => 'quantity', 'label' => 'Qty', 'sortable' => true],
                ['key' => 'unit_cost', 'label' => 'WAC', 'sortable' => true],
                ['key' => 'value', 'label' => 'Value', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            summary: ['total_value' => round(array_sum(array_column($all, 'value')), 2)],
            totalRows: $this->totalRowCount($all, $filters),
        );
    }
}