<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryReconciliationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryReconciliationController extends Controller
{
    public function __invoke(Request $request, InventoryReconciliationService $reconciliation): Response
    {
        $this->authorize('viewModule', 'inventory');

        return Inertia::render('Inventory/Reconciliation', [
            'pageTitle' => 'Stock reconciliation',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Inventory', 'href' => route('erp.inventory')],
                ['label' => 'Reconciliation'],
            ],
            'mismatches' => $reconciliation->compareBalancesToLedger(),
        ]);
    }
}