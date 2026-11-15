<?php

namespace App\Http\Controllers\Dana;

use App\Http\Controllers\Controller;
use App\Models\DanaBatch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DanaCostingReportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewModule', 'dana-production');

        $from = $request->date('date_from') ?? now()->subDays(30)->toDateString();
        $to = $request->date('date_to') ?? now()->toDateString();

        $rows = DanaBatch::query()
            ->where('status', 'posted')
            ->whereBetween('posted_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->with(['machine:id,name', 'outputMaterial:id,name'])
            ->orderByDesc('posted_at')
            ->get()
            ->map(fn (DanaBatch $b) => [
                'batch_number' => $b->batch_number,
                'production_date' => $b->production_date->format('Y-m-d'),
                'machine' => $b->machine?->name,
                'output_kg' => (float) $b->output_kg,
                'raw_material_cost' => (float) $b->raw_material_cost,
                'color_material_cost' => (float) $b->color_material_cost,
                'direct_labor_cost' => (float) $b->direct_labor_cost,
                'electricity_cost' => (float) $b->electricity_cost,
                'other_overhead_cost' => (float) $b->other_overhead_cost,
                'total_batch_cost' => (float) $b->total_batch_cost,
                'cost_per_kg' => (float) $b->cost_per_kg,
            ]);

        $totals = [
            'output_kg' => $rows->sum('output_kg'),
            'total_batch_cost' => $rows->sum('total_batch_cost'),
        ];

        return Inertia::render('Dana/Reports/Costing', [
            'pageTitle' => 'Dana costing report',
            'breadcrumbs' => $this->breadcrumbs('Costing'),
            'rows' => $rows,
            'totals' => $totals,
            'filters' => ['date_from' => $from, 'date_to' => $to],
        ]);
    }

    /**
     * @return array<int, array{label: string, href?: string}>
     */
    private function breadcrumbs(string $leaf): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Dana Production', 'href' => route('erp.dana-production')],
            ['label' => $leaf],
        ];
    }
}