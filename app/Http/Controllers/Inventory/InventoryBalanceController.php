<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryBalance;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryBalanceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'inventory');

        $query = InventoryBalance::query()
            ->with(['warehouse', 'material.unit', 'variant'])
            ->where('quantity', '>', 0);

        if ($warehouseId = $request->integer('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->whereHas('material', function ($builder) use ($search) {
                $builder->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%');
            });
        }

        $balances = $query->orderBy('warehouse_id')->paginate(20)->withQueryString();

        $lowStock = InventoryBalance::query()
            ->with(['material', 'warehouse'])
            ->join('materials', 'materials.id', '=', 'inventory_balances.material_id')
            ->whereColumn('inventory_balances.quantity', '<', 'materials.reorder_level')
            ->select('inventory_balances.*')
            ->limit(10)
            ->get();

        return Inertia::render('Inventory/Balances/Index', [
            'pageTitle' => 'Current stock',
            'breadcrumbs' => $this->breadcrumbs(),
            'balances' => $balances,
            'lowStock' => $lowStock,
            'filters' => [
                'search' => $search,
                'warehouse_id' => $warehouseId ?: null,
            ],
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
            ['label' => 'Current stock'],
        ];
    }
}