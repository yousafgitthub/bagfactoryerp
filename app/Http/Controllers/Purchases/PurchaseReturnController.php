<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Purchases\PurchaseReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseReturnController extends Controller
{
    public function __construct(
        private readonly PurchaseReturnService $returns,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'purchases');

        $returns = PurchaseReturn::query()
            ->with(['supplier', 'warehouse'])
            ->orderByDesc('return_date')
            ->paginate(15);

        return Inertia::render('Purchases/Returns/Index', [
            'pageTitle' => 'Purchase returns',
            'breadcrumbs' => $this->crumbs('Returns'),
            'returns' => $returns,
            'canManage' => $request->user()?->can('purchases.manage') === true,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('managePurchases');

        return Inertia::render('Purchases/Returns/Form', [
            'pageTitle' => 'New purchase return',
            'breadcrumbs' => $this->crumbs('New return'),
            'suppliers' => Supplier::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']),
            'warehouses' => Warehouse::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('managePurchases');

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'goods_receipt_id' => ['nullable', 'exists:goods_receipts,id'],
            'return_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.material_id' => ['required', 'exists:materials,id'],
            'lines.*.material_variant_id' => ['nullable', 'integer'],
            'lines.*.purchase_order_line_id' => ['nullable', 'integer'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'gte:0'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'gte:0'],
        ]);

        $supplier = Supplier::query()->findOrFail($validated['supplier_id']);
        $header = collect($validated)->except(['lines', 'supplier_id'])->all();

        $document = $this->returns->createDraft($supplier, $header, $validated['lines'], $request->user());

        return redirect()->route('purchases.returns.show', $document)->with('success', 'Return draft created.');
    }

    public function show(Request $request, PurchaseReturn $purchaseReturn): Response
    {
        $this->authorize('viewModule', 'purchases');

        $purchaseReturn->load(['supplier', 'warehouse', 'lines.material', 'inventoryTransaction']);

        return Inertia::render('Purchases/Returns/Show', [
            'pageTitle' => $purchaseReturn->return_number,
            'breadcrumbs' => $this->crumbs($purchaseReturn->return_number),
            'purchaseReturn' => $purchaseReturn,
            'canManage' => $request->user()?->can('purchases.manage') === true,
        ]);
    }

    public function post(Request $request, PurchaseReturn $purchaseReturn): RedirectResponse
    {
        $this->authorize('managePurchases');

        $this->returns->post($purchaseReturn, $request->user());

        return back()->with('success', 'Purchase return posted.');
    }

    /** @return list<array{label: string, href?: string}> */
    private function crumbs(string $tail): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Purchases', 'href' => route('erp.purchases')],
            ['label' => $tail],
        ];
    }
}