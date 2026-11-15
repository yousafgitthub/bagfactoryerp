<?php

namespace App\Services\Reports;

use App\Models\BagProductionBatch;
use App\Models\BagProductionOrder;
use App\Models\DanaBatch;
use App\Services\Reports\Concerns\PaginatesReportRows;
use Illuminate\Support\Facades\DB;

class ProductionReportService
{
    use PaginatesReportRows;

    public function danaProduction(ReportFilters $filters): ReportResult
    {
        $dateExpr = $filters->granularity === 'month'
            ? "DATE_FORMAT(production_date, '%Y-%m')"
            : 'production_date';

        $query = DanaBatch::query()
            ->selectRaw("{$dateExpr} as period")
            ->selectRaw('COUNT(*) as batch_count')
            ->selectRaw('SUM(output_kg) as output_kg')
            ->selectRaw('SUM(total_input_kg) as input_kg')
            ->selectRaw('SUM(reusable_waste_kg + scrap_waste_kg + process_loss_kg) as waste_kg')
            ->selectRaw('SUM(total_batch_cost) as total_cost')
            ->where('status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('production_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('production_date', '<=', $filters->to))
            ->when($filters->machineId, fn ($q) => $q->where('machine_id', $filters->machineId))
            ->groupBy('period')
            ->orderBy('period');

        $all = $query->get()->map(fn ($r) => [
            'period' => (string) $r->period,
            'batch_count' => (int) $r->batch_count,
            'input_kg' => round((float) $r->input_kg, 2),
            'output_kg' => round((float) $r->output_kg, 2),
            'waste_kg' => round((float) $r->waste_kg, 2),
            'total_cost' => round((float) $r->total_cost, 2),
        ])->all();

        $totalOutput = array_sum(array_column($all, 'output_kg'));

        return new ReportResult(
            title: 'Dana production',
            description: 'Posted Dana batches aggregated from production ledger (not estimates).',
            columns: [
                ['key' => 'period', 'label' => 'Period', 'sortable' => true],
                ['key' => 'batch_count', 'label' => 'Batches', 'sortable' => true],
                ['key' => 'input_kg', 'label' => 'Input (kg)', 'sortable' => true],
                ['key' => 'output_kg', 'label' => 'Output (kg)', 'sortable' => true],
                ['key' => 'waste_kg', 'label' => 'Waste (kg)', 'sortable' => true],
                ['key' => 'total_cost', 'label' => 'Batch cost', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            summary: ['total_output_kg' => round($totalOutput, 2)],
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function bagProduction(ReportFilters $filters): ReportResult
    {
        $dateExpr = $filters->granularity === 'month'
            ? "DATE_FORMAT(production_date, '%Y-%m')"
            : 'production_date';

        $query = BagProductionBatch::query()
            ->selectRaw("{$dateExpr} as period")
            ->selectRaw('COUNT(*) as batch_count')
            ->selectRaw('SUM(good_quantity) as good_bags')
            ->selectRaw('SUM(rejected_quantity) as rejected_bags')
            ->selectRaw('SUM(dana_consumed_kg) as dana_kg')
            ->selectRaw('SUM(total_batch_cost) as total_cost')
            ->where('status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('production_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('production_date', '<=', $filters->to))
            ->when($filters->machineId, fn ($q) => $q->where('machine_id', $filters->machineId))
            ->groupBy('period')
            ->orderBy('period');

        $all = $query->get()->map(fn ($r) => [
            'period' => (string) $r->period,
            'batch_count' => (int) $r->batch_count,
            'good_bags' => round((float) $r->good_bags, 2),
            'rejected_bags' => round((float) $r->rejected_bags, 2),
            'dana_kg' => round((float) $r->dana_kg, 2),
            'total_cost' => round((float) $r->total_cost, 2),
        ])->all();

        return new ReportResult(
            title: 'Shopping bag production',
            description: 'Posted bag batches — good/rejected quantities from batch records.',
            columns: [
                ['key' => 'period', 'label' => 'Period', 'sortable' => true],
                ['key' => 'batch_count', 'label' => 'Batches', 'sortable' => true],
                ['key' => 'good_bags', 'label' => 'Good bags', 'sortable' => true],
                ['key' => 'rejected_bags', 'label' => 'Rejected', 'sortable' => true],
                ['key' => 'dana_kg', 'label' => 'Dana used (kg)', 'sortable' => true],
                ['key' => 'total_cost', 'label' => 'Batch cost', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            summary: ['good_bags_total' => round(array_sum(array_column($all, 'good_bags')), 2)],
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function machineWise(ReportFilters $filters): ReportResult
    {
        $dana = DanaBatch::query()
            ->select('machine_id')
            ->selectRaw('SUM(output_kg) as dana_kg')
            ->selectRaw('COUNT(*) as dana_batches')
            ->where('status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('production_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('production_date', '<=', $filters->to))
            ->groupBy('machine_id')
            ->get()
            ->keyBy('machine_id');

        $bag = BagProductionBatch::query()
            ->select('machine_id')
            ->selectRaw('SUM(good_quantity) as good_bags')
            ->selectRaw('COUNT(*) as bag_batches')
            ->where('status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('production_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('production_date', '<=', $filters->to))
            ->groupBy('machine_id')
            ->get()
            ->keyBy('machine_id');

        $machineIds = $dana->keys()->merge($bag->keys())->unique()->filter();

        $machines = DB::table('machines')->whereIn('id', $machineIds)->pluck('name', 'id');

        $all = [];
        foreach ($machineIds as $id) {
            $all[] = [
                'machine' => $machines[$id] ?? ('#'.$id),
                'dana_kg' => round((float) ($dana[$id]->dana_kg ?? 0), 2),
                'dana_batches' => (int) ($dana[$id]->dana_batches ?? 0),
                'good_bags' => round((float) ($bag[$id]->good_bags ?? 0), 2),
                'bag_batches' => (int) ($bag[$id]->bag_batches ?? 0),
            ];
        }

        return new ReportResult(
            title: 'Machine-wise production',
            description: 'Dana output (kg) and bag good quantity by machine.',
            columns: [
                ['key' => 'machine', 'label' => 'Machine', 'sortable' => true],
                ['key' => 'dana_kg', 'label' => 'Dana output (kg)', 'sortable' => true],
                ['key' => 'dana_batches', 'label' => 'Dana batches', 'sortable' => true],
                ['key' => 'good_bags', 'label' => 'Good bags', 'sortable' => true],
                ['key' => 'bag_batches', 'label' => 'Bag batches', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function hallWise(ReportFilters $filters): ReportResult
    {
        $dana = DanaBatch::query()
            ->select('production_hall_id')
            ->selectRaw('SUM(output_kg) as dana_kg')
            ->where('status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('production_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('production_date', '<=', $filters->to))
            ->groupBy('production_hall_id')
            ->get()
            ->keyBy('production_hall_id');

        $bag = BagProductionBatch::query()
            ->select('production_hall_id')
            ->selectRaw('SUM(good_quantity) as good_bags')
            ->where('status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('production_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('production_date', '<=', $filters->to))
            ->groupBy('production_hall_id')
            ->get()
            ->keyBy('production_hall_id');

        $hallIds = $dana->keys()->merge($bag->keys())->unique()->filter();
        $halls = DB::table('production_halls')->whereIn('id', $hallIds)->pluck('name', 'id');

        $all = [];
        foreach ($hallIds as $id) {
            $all[] = [
                'hall' => $halls[$id] ?? ('#'.$id),
                'dana_kg' => round((float) ($dana[$id]->dana_kg ?? 0), 2),
                'good_bags' => round((float) ($bag[$id]->good_bags ?? 0), 2),
            ];
        }

        return new ReportResult(
            title: 'Hall-wise production',
            description: 'Output grouped by production hall.',
            columns: [
                ['key' => 'hall', 'label' => 'Hall', 'sortable' => true],
                ['key' => 'dana_kg', 'label' => 'Dana output (kg)', 'sortable' => true],
                ['key' => 'good_bags', 'label' => 'Good bags', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function colorWise(ReportFilters $filters): ReportResult
    {
        $dana = DB::table('dana_batches as b')
            ->join('material_variants as v', 'v.id', '=', 'b.output_material_variant_id')
            ->leftJoin('colors as c', 'c.id', '=', 'v.color_id')
            ->where('b.status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('b.production_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('b.production_date', '<=', $filters->to))
            ->selectRaw("COALESCE(c.name, v.name, 'Unspecified') as color_label")
            ->selectRaw('SUM(b.output_kg) as dana_kg')
            ->groupBy('color_label')
            ->get();

        $bag = DB::table('bag_production_batches as b')
            ->join('bag_production_orders as o', 'o.id', '=', 'b.bag_production_order_id')
            ->leftJoin('colors as c', 'c.id', '=', 'o.color_id')
            ->where('b.status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('b.production_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('b.production_date', '<=', $filters->to))
            ->selectRaw("COALESCE(c.name, 'Unspecified') as color_label")
            ->selectRaw('SUM(b.good_quantity) as good_bags')
            ->groupBy('color_label')
            ->get()
            ->keyBy('color_label');

        $all = [];
        foreach ($dana as $row) {
            $label = $row->color_label;
            $all[] = [
                'color' => $label,
                'dana_kg' => round((float) $row->dana_kg, 2),
                'good_bags' => round((float) ($bag[$label]->good_bags ?? 0), 2),
            ];
        }

        foreach ($bag as $label => $row) {
            if (collect($all)->firstWhere('color', $label)) {
                continue;
            }
            $all[] = [
                'color' => $label,
                'dana_kg' => 0,
                'good_bags' => round((float) $row->good_bags, 2),
            ];
        }

        return new ReportResult(
            title: 'Color-wise production',
            description: 'Dana and bag output by color / variant.',
            columns: [
                ['key' => 'color', 'label' => 'Color', 'sortable' => true],
                ['key' => 'dana_kg', 'label' => 'Dana output (kg)', 'sortable' => true],
                ['key' => 'good_bags', 'label' => 'Good bags', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function employeeWise(ReportFilters $filters): ReportResult
    {
        $dana = DB::table('employee_production_assignments as a')
            ->join('dana_batches as b', 'b.id', '=', 'a.dana_batch_id')
            ->join('employees as e', 'e.id', '=', 'a.employee_id')
            ->where('b.status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('b.production_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('b.production_date', '<=', $filters->to))
            ->selectRaw('e.id as employee_id, e.employee_code, e.name')
            ->selectRaw('COUNT(DISTINCT b.id) as dana_batches')
            ->selectRaw('SUM(b.output_kg) as dana_kg')
            ->groupBy('e.id', 'e.employee_code', 'e.name')
            ->get()
            ->keyBy('employee_id');

        $bag = DB::table('employee_production_assignments as a')
            ->join('bag_production_batches as b', 'b.id', '=', 'a.bag_production_batch_id')
            ->join('employees as e', 'e.id', '=', 'a.employee_id')
            ->where('b.status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('b.production_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('b.production_date', '<=', $filters->to))
            ->selectRaw('e.id as employee_id, e.employee_code, e.name')
            ->selectRaw('COUNT(DISTINCT b.id) as bag_batches')
            ->selectRaw('SUM(b.good_quantity) as good_bags')
            ->groupBy('e.id', 'e.employee_code', 'e.name')
            ->get()
            ->keyBy('employee_id');

        $ids = $dana->keys()->merge($bag->keys())->unique();

        $all = [];
        foreach ($ids as $id) {
            $d = $dana[$id] ?? null;
            $g = $bag[$id] ?? null;
            $all[] = [
                'employee' => ($d?->name ?? $g?->name).' ('.($d?->employee_code ?? $g?->employee_code).')',
                'dana_batches' => (int) ($d->dana_batches ?? 0),
                'dana_kg' => round((float) ($d->dana_kg ?? 0), 2),
                'bag_batches' => (int) ($g->bag_batches ?? 0),
                'good_bags' => round((float) ($g->good_bags ?? 0), 2),
            ];
        }

        return new ReportResult(
            title: 'Employee-wise production',
            description: 'Operator assignments on posted Dana and bag batches.',
            columns: [
                ['key' => 'employee', 'label' => 'Employee', 'sortable' => true],
                ['key' => 'dana_batches', 'label' => 'Dana batches', 'sortable' => true],
                ['key' => 'dana_kg', 'label' => 'Dana output (kg)', 'sortable' => true],
                ['key' => 'bag_batches', 'label' => 'Bag batches', 'sortable' => true],
                ['key' => 'good_bags', 'label' => 'Good bags', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function orderBatchStatus(ReportFilters $filters): ReportResult
    {
        $bagOrders = BagProductionOrder::query()
            ->withCount(['batches as posted_batches' => fn ($q) => $q->where('status', 'posted')])
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->map(fn ($o) => [
                'order_number' => $o->order_number,
                'line' => 'Bag',
                'status' => $o->status,
                'planned' => (float) $o->planned_quantity,
                'produced' => (float) $o->produced_quantity,
                'posted_batches' => (int) $o->posted_batches,
            ])
            ->all();

        $danaBatches = DanaBatch::query()
            ->select('batch_number', 'status', 'output_kg', 'production_date')
            ->when($filters->from, fn ($q) => $q->whereDate('production_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('production_date', '<=', $filters->to))
            ->orderByDesc('production_date')
            ->limit(500)
            ->get()
            ->map(fn ($b) => [
                'order_number' => $b->batch_number,
                'line' => 'Dana batch',
                'status' => $b->status,
                'planned' => '',
                'produced' => (float) $b->output_kg,
                'posted_batches' => $b->status === 'posted' ? 1 : 0,
            ])
            ->all();

        $all = array_merge($bagOrders, $danaBatches);

        return new ReportResult(
            title: 'Production orders & batch status',
            description: 'Bag production orders and Dana batch lifecycle.',
            columns: [
                ['key' => 'order_number', 'label' => 'Reference', 'sortable' => true],
                ['key' => 'line', 'label' => 'Type', 'sortable' => true],
                ['key' => 'status', 'label' => 'Status', 'sortable' => true],
                ['key' => 'planned', 'label' => 'Planned / input', 'sortable' => true],
                ['key' => 'produced', 'label' => 'Produced / output', 'sortable' => true],
                ['key' => 'posted_batches', 'label' => 'Posted batches', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function yieldAndWaste(ReportFilters $filters): ReportResult
    {
        $dana = DanaBatch::query()
            ->where('status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('production_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('production_date', '<=', $filters->to))
            ->selectRaw('SUM(total_input_kg) as input_kg, SUM(output_kg) as good_kg')
            ->selectRaw('SUM(reusable_waste_kg + scrap_waste_kg + process_loss_kg) as waste_kg')
            ->first();

        $bag = BagProductionBatch::query()
            ->where('status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('production_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('production_date', '<=', $filters->to))
            ->selectRaw('SUM(good_quantity + rejected_quantity) as total_pcs')
            ->selectRaw('SUM(good_quantity) as good_pcs')
            ->selectRaw('SUM(reusable_waste_kg + scrap_waste_kg + process_loss_kg) as waste_kg')
            ->first();

        $danaInput = (float) ($dana->input_kg ?? 0);
        $danaGood = (float) ($dana->good_kg ?? 0);
        $danaWaste = (float) ($dana->waste_kg ?? 0);
        $danaYield = $danaInput > 0 ? round(($danaGood / $danaInput) * 100, 2) : 0;
        $danaWastePct = $danaInput > 0 ? round(($danaWaste / $danaInput) * 100, 2) : 0;

        $bagTotal = (float) ($bag->total_pcs ?? 0);
        $bagGood = (float) ($bag->good_pcs ?? 0);
        $bagYield = $bagTotal > 0 ? round(($bagGood / $bagTotal) * 100, 2) : 0;

        $all = [
            [
                'line' => 'Dana',
                'good_output' => round($danaGood, 2),
                'total_input' => round($danaInput, 2),
                'waste' => round($danaWaste, 2),
                'yield_pct' => $danaYield,
                'waste_pct' => $danaWastePct,
            ],
            [
                'line' => 'Shopping bags',
                'good_output' => round($bagGood, 2),
                'total_input' => round($bagTotal, 2),
                'waste' => round((float) ($bag->waste_kg ?? 0), 2),
                'yield_pct' => $bagYield,
                'waste_pct' => $bagTotal > 0 ? round((($bagTotal - $bagGood) / $bagTotal) * 100, 2) : 0,
            ],
        ];

        return new ReportResult(
            title: 'Production yield & waste',
            description: 'Yield and waste percentages from posted batch quantities.',
            columns: [
                ['key' => 'line', 'label' => 'Line', 'sortable' => true],
                ['key' => 'good_output', 'label' => 'Good output', 'sortable' => true],
                ['key' => 'total_input', 'label' => 'Total input', 'sortable' => true],
                ['key' => 'waste', 'label' => 'Waste qty', 'sortable' => true],
                ['key' => 'yield_pct', 'label' => 'Yield %', 'sortable' => true],
                ['key' => 'waste_pct', 'label' => 'Waste %', 'sortable' => true],
            ],
            rows: $all,
            filters: $filters->toArray(),
            totalRows: count($all),
        );
    }
}