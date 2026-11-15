<?php

namespace App\Http\Controllers\Bag;

use App\Http\Controllers\Controller;
use App\Models\BagProductionBatch;
use App\Models\BagProductionOrder;
use App\Models\InventoryBalance;
use App\Models\Machine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BagReportController extends Controller
{
    public function costing(Request $request): Response
    {
        $this->authorize('viewModule', 'shopping-bag-production');

        $rows = BagProductionBatch::query()
            ->where('status', 'posted')
            ->with(['order.variant.product', 'machine:id,name'])
            ->when($request->date('date_from'), fn ($q) => $q->whereDate('production_date', '>=', $request->date('date_from')))
            ->when($request->date('date_to'), fn ($q) => $q->whereDate('production_date', '<=', $request->date('date_to')))
            ->orderByDesc('posted_at')
            ->limit(200)
            ->get();

        return Inertia::render('Bag/Reports/Costing', [
            'pageTitle' => 'Bag production costing',
            'breadcrumbs' => $this->breadcrumbs('Costing'),
            'rows' => $rows,
            'filters' => $request->only(['date_from', 'date_to']),
            'reportLinks' => $this->reportLinks(),
        ]);
    }

    public function planning(): Response
    {
        $this->authorize('viewModule', 'shopping-bag-production');

        $orders = BagProductionOrder::query()
            ->with(['product:id,code,name', 'variant:id,variant_code,name', 'machine:id,name'])
            ->whereIn('status', ['released', 'in_progress'])
            ->orderBy('planned_start_date')
            ->get();

        return Inertia::render('Bag/Reports/Planning', [
            'pageTitle' => 'Production planning',
            'breadcrumbs' => $this->breadcrumbs('Planning'),
            'orders' => $orders,
            'reportLinks' => $this->reportLinks(),
        ]);
    }

    public function machineOutput(Request $request): Response
    {
        $this->authorize('viewModule', 'shopping-bag-production');

        $from = $request->date('date_from') ?? now()->startOfMonth()->toDateString();
        $to = $request->date('date_to') ?? now()->toDateString();

        $rows = BagProductionBatch::query()
            ->select('machine_id', DB::raw('SUM(good_quantity) as good_bags'), DB::raw('SUM(rejected_quantity) as rejected_bags'), DB::raw('COUNT(*) as batch_count'))
            ->where('status', 'posted')
            ->whereBetween('production_date', [$from, $to])
            ->groupBy('machine_id')
            ->with('machine:id,name')
            ->get();

        return Inertia::render('Bag/Reports/MachineOutput', [
            'pageTitle' => 'Machine-wise production',
            'breadcrumbs' => $this->breadcrumbs('Machine output'),
            'rows' => $rows,
            'filters' => ['date_from' => $from, 'date_to' => $to],
            'machines' => Machine::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'reportLinks' => $this->reportLinks(),
        ]);
    }

    public function finishedStock(): Response
    {
        $this->authorize('viewModule', 'shopping-bag-production');

        $balances = InventoryBalance::query()
            ->where('bucket', 'finished_bag')
            ->with(['material:id,code,name', 'warehouse:id,name'])
            ->orderBy('warehouse_id')
            ->get();

        return Inertia::render('Bag/Reports/FinishedStock', [
            'pageTitle' => 'Finished bag inventory',
            'breadcrumbs' => $this->breadcrumbs('Finished stock'),
            'balances' => $balances,
            'reportLinks' => $this->reportLinks(),
        ]);
    }

    /**
     * @return list<array{label: string, href: string}>
     */
    private function reportLinks(): array
    {
        return [
            ['label' => 'Costing', 'href' => route('bag.reports.costing')],
            ['label' => 'Planning', 'href' => route('bag.reports.planning')],
            ['label' => 'Machine output', 'href' => route('bag.reports.machine')],
            ['label' => 'Finished stock', 'href' => route('bag.reports.finished-stock')],
        ];
    }

    /**
     * @return array<int, array{label: string, href?: string}>
     */
    private function breadcrumbs(string $leaf): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Bag Production', 'href' => route('erp.shopping-bag-production')],
            ['label' => $leaf],
        ];
    }
}