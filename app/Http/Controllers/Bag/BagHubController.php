<?php

namespace App\Http\Controllers\Bag;

use App\Http\Controllers\Controller;
use App\Models\BagProductionBatch;
use App\Models\BagProductionOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BagHubController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewModule', 'shopping-bag-production');

        $postedQuery = BagProductionBatch::query()->where('status', 'posted');

        $summary = [
            'posted_batches' => (clone $postedQuery)->count(),
            'good_bags_mtd' => (float) (clone $postedQuery)
                ->whereBetween('posted_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('good_quantity'),
            'open_orders' => BagProductionOrder::query()->whereIn('status', ['released', 'in_progress'])->count(),
            'draft_batches' => BagProductionBatch::query()->where('status', 'draft')->count(),
        ];

        $recent = BagProductionBatch::query()
            ->with(['machine:id,name', 'order.variant.product'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return Inertia::render('Bag/Hub', [
            'pageTitle' => 'Shopping Bag Production',
            'breadcrumbs' => $this->breadcrumbs('Overview'),
            'summary' => $summary,
            'recentBatches' => $recent,
            'canManage' => $request->user()?->can('shopping-bag-production.manage') === true,
            'links' => [
                [
                    'label' => 'Products & variants',
                    'description' => 'Bag catalog, sizes, colors and inventory materials.',
                    'href' => route('bag.products.index'),
                ],
                [
                    'label' => 'Bill of materials',
                    'description' => 'Versioned recipes with Dana and additives per bag.',
                    'href' => route('bag.bom.index'),
                ],
                [
                    'label' => 'Production orders',
                    'description' => 'Plan quantities and track partial completion.',
                    'href' => route('bag.orders.index'),
                ],
                [
                    'label' => 'Production batches',
                    'description' => 'Record output, post consumption and finished stock.',
                    'href' => route('bag.batches.index'),
                ],
                [
                    'label' => 'Costing & planning',
                    'description' => 'Posted batch costs, machine output and FG stock.',
                    'href' => route('bag.reports.costing'),
                ],
            ],
        ]);
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