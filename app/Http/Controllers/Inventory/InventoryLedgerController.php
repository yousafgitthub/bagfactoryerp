<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryLedgerEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryLedgerController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'inventory');

        $query = InventoryLedgerEntry::query()
            ->with(['material', 'warehouse', 'transaction'])
            ->orderByDesc('occurred_at');

        if ($movement = $request->string('movement_type')->trim()->toString()) {
            $query->where('movement_type', $movement);
        }

        $entries = $query->paginate(25)->withQueryString();

        return Inertia::render('Inventory/Ledger/Index', [
            'pageTitle' => 'Movement history',
            'breadcrumbs' => $this->breadcrumbs(),
            'entries' => $entries,
            'filters' => ['movement_type' => $movement],
        ]);
    }

    /**
     * @return array<int, array{label: string, href?: string}>
     */
    private function breadcrumbs(): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Inventory', 'href' => route('erp.inventory')],
            ['label' => 'Movement history'],
        ];
    }
}