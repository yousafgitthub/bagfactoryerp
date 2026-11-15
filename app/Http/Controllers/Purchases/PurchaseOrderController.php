<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\UnitOfMeasurement;
use App\Models\Warehouse;
use App\Services\Purchases\GoodsReceiptService;
use App\Services\Purchases\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderService $orders,
        private readonly GoodsReceiptService $receipts,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'purchases');

        $orders = PurchaseOrder::query()
            ->with(['supplier', 'warehouse'])
            ->when($status = $request->string('status')->trim()->toString(), fn ($q) => $q->where('status', $status))
            ->orderByDesc('order_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Purchases/Orders/Index', [
            'pageTitle' => 'Purchase orders',
            'breadcrumbs' => $this->crumbs('Purchase orders'),
            'orders' => $orders,
            'statuses' => config('purchases.purchase_order_statuses'),
            'filters' => ['status' => $request->string('status')->toString()],
            'canManage' => $request->user()?->can('purchases.manage') === true,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('managePurchases');

        return Inertia::render('Purchases/Orders/Form', [
            'pageTitle' => 'New purchase order',
            'breadcrumbs' => $this->crumbs('New PO'),
            'order' => null,
            'lookups' => $this->lookups(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('managePurchases');

        $validated = $this->validateOrder($request);
        $order = $this->orders->createDraft($validated['header'], $validated['lines'], $request->user());

        return redirect()->route('purchases.orders.show', $order)->with('success', 'Purchase order saved as draft.');
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder): Response
    {
        $this->authorize('viewModule', 'purchases');

        $purchaseOrder->load(['supplier', 'warehouse', 'lines.material', 'lines.unit', 'goodsReceipts']);

        return Inertia::render('Purchases/Orders/Show', [
            'pageTitle' => $purchaseOrder->order_number,
            'breadcrumbs' => $this->crumbs($purchaseOrder->order_number),
            'order' => $purchaseOrder,
            'statuses' => config('purchases.purchase_order_statuses'),
            'canManage' => $request->user()?->can('purchases.manage') === true,
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder): Response|RedirectResponse
    {
        $this->authorize('managePurchases');

        if (! $purchaseOrder->isDraft()) {
            return redirect()->route('purchases.orders.show', $purchaseOrder);
        }

        $purchaseOrder->load(['lines.material', 'lines.unit']);

        return Inertia::render('Purchases/Orders/Form', [
            'pageTitle' => 'Edit purchase order',
            'breadcrumbs' => $this->crumbs('Edit PO'),
            'order' => $purchaseOrder,
            'lookups' => $this->lookups(),
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('managePurchases');

        $validated = $this->validateOrder($request);
        $this->orders->updateDraft($purchaseOrder, $validated['header'], $validated['lines']);

        return redirect()->route('purchases.orders.show', $purchaseOrder)->with('success', 'Purchase order updated.');
    }

    public function confirm(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('managePurchases');

        $this->orders->confirm($purchaseOrder, $request->user());

        return back()->with('success', 'Purchase order confirmed.');
    }

    public function storeGoodsReceipt(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('managePurchases');

        $data = $request->validate([
            'receipt_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_order_line_id' => ['required', 'integer'],
            'lines.*.received_qty' => ['required', 'numeric', 'gt:0'],
        ]);

        $grn = $this->receipts->createDraft(
            $purchaseOrder,
            $data['receipt_date'],
            $data['lines'],
            $request->user(),
            $data['notes'] ?? null,
        );

        return redirect()->route('purchases.goods-receipts.show', $grn)->with('success', 'Goods receipt draft created.');
    }

    /** @return array{header: array<string, mixed>, lines: list<array<string, mixed>>} */
    private function validateOrder(Request $request): array
    {
        $header = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $linesInput = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.material_id' => ['required', 'exists:materials,id'],
            'lines.*.material_variant_id' => ['nullable', 'exists:material_variants,id'],
            'lines.*.unit_of_measurement_id' => ['required', 'exists:units_of_measurement,id'],
            'lines.*.ordered_qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'gte:0'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'gte:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'gte:0'],
        ])['lines'];

        return ['header' => $header, 'lines' => $linesInput];
    }

    private function lookups(): array
    {
        return [
            'suppliers' => Supplier::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']),
            'warehouses' => Warehouse::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']),
            'materials' => Material::query()->where('status', 'active')->with('unit:id,code,name')->orderBy('code')->get(['id', 'code', 'name', 'unit_of_measurement_id']),
            'units' => UnitOfMeasurement::query()->where('status', 'active')->orderBy('code')->get(['id', 'code', 'name']),
        ];
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