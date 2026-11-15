<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryHubController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewModule', 'inventory');

        $lowStockCount = (int) \Illuminate\Support\Facades\DB::table('inventory_balances')
            ->join('materials', 'materials.id', '=', 'inventory_balances.material_id')
            ->where('materials.status', 'active')
            ->whereColumn('inventory_balances.quantity', '<', 'materials.reorder_level')
            ->count();

        $links = [
            ['label' => 'Materials', 'href' => route('inventory.materials.index'), 'description' => 'Raw materials, variants, and reorder levels.'],
            ['label' => 'Current stock', 'href' => route('inventory.balances.index'), 'description' => 'On-hand quantities and weighted average cost.'],
            ['label' => 'Stock movements', 'href' => route('inventory.transactions.index'), 'description' => 'Draft and posted receipts, issues, transfers, and adjustments.'],
            ['label' => 'Movement history', 'href' => route('inventory.ledger.index'), 'description' => 'Immutable ledger audit trail.'],
            ['label' => 'Reconciliation', 'href' => route('inventory.reconciliation'), 'description' => 'Compare balances with ledger totals.'],
        ];

        return Inertia::render('Inventory/Hub', [
            'pageTitle' => 'Inventory',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Inventory'],
            ],
            'lowStockCount' => $lowStockCount,
            'links' => $links,
            'canManage' => $request->user()?->can('inventory.manage') === true,
            'transactionTypes' => config('inventory.transaction_types'),
        ]);
    }
}