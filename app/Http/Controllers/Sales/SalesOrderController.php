<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\BagProduct;
use App\Models\BagProductVariant;
use App\Models\Customer;
use App\Models\Material;
use App\Models\SalesOrder;
use App\Models\UnitOfMeasurement;
use App\Models\Warehouse;
use App\Services\Sales\SalesInvoiceService;
use App\Services\Sales\SalesOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesOrderController extends Controller
{
    public function __construct(
        private readonly SalesOrderService $orders,
        private readonly SalesInvoiceService $invoices,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'sales');

        $orders = SalesOrder::query()
            ->with(['customer', 'warehouse'])
            ->when($status = $request->string('status')->trim()->toString(), fn ($q) => $q->where('status', $status))
            ->orderByDesc('order_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Sales/Orders/Index', [
            'pageTitle' => 'Sales orders',
            'breadcrumbs' => $this->crumbs('Sales orders'),
            'orders' => $orders,
            'statuses' => config('sales.sales_order_statuses'),
            'filters' => ['status' => $request->string('status')->toString()],
            'canManage' => $request->user()?->can('sales.manage') === true,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('manageSales');

        return Inertia::render('Sales/Orders/Form', [
            'pageTitle' => 'New sales order',
            'breadcrumbs' => $this->crumbs('New SO'),
            'order' => null,
            'lookups' => $this->lookups(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manageSales');

        $validated = $this->validateOrder($request);
        $order = $this->orders->createDraft($validated['header'], $validated['lines'], $request->user());

        return redirect()->route('sales.orders.show', $order)->with('success', 'Sales order saved as draft.');
    }

    public function show(Request $request, SalesOrder $salesOrder): Response
    {
        $this->authorize('viewModule', 'sales');

        $salesOrder->load(['customer', 'warehouse', 'lines.material', 'lines.bagProduct', 'lines.bagProductVariant']);

        return Inertia::render('Sales/Orders/Show', [
            'pageTitle' => $salesOrder->order_number,
            'breadcrumbs' => $this->crumbs($salesOrder->order_number),
            'order' => $salesOrder,
            'canManage' => $request->user()?->can('sales.manage') === true,
        ]);
    }

    public function edit(SalesOrder $salesOrder): Response|RedirectResponse
    {
        $this->authorize('manageSales');

        if (! $salesOrder->isDraft()) {
            return redirect()->route('sales.orders.show', $salesOrder);
        }

        $salesOrder->load(['lines.material']);

        return Inertia::render('Sales/Orders/Form', [
            'pageTitle' => 'Edit sales order',
            'breadcrumbs' => $this->crumbs('Edit SO'),
            'order' => $salesOrder,
            'lookups' => $this->lookups(),
        ]);
    }

    public function update(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('manageSales');

        $validated = $this->validateOrder($request);
        $this->orders->updateDraft($salesOrder, $validated['header'], $validated['lines']);

        return redirect()->route('sales.orders.show', $salesOrder)->with('success', 'Sales order updated.');
    }

    public function confirm(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('manageSales');

        $this->orders->confirm($salesOrder, $request->user());

        return back()->with('success', 'Sales order confirmed.');
    }

    public function storeInvoice(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('manageSales');

        $data = $request->validate([
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'sale_type' => ['required', 'in:cash,credit'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.sales_order_line_id' => ['required', 'integer'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        $header = collect($data)->except('lines')->all();
        $invoice = $this->invoices->createFromSalesOrder($salesOrder, $header, $data['lines'], $request->user());

        return redirect()->route('sales.invoices.show', $invoice)->with('success', 'Draft invoice created from sales order.');
    }

    /** @return array{header: array<string, mixed>, lines: list<array<string, mixed>>} */
    private function validateOrder(Request $request): array
    {
        $header = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $linesInput = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.bag_product_variant_id' => ['nullable', 'exists:bag_product_variants,id'],
            'lines.*.bag_product_id' => ['nullable', 'exists:bag_products,id'],
            'lines.*.material_id' => ['nullable', 'exists:materials,id'],
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
            'customers' => Customer::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']),
            'warehouses' => Warehouse::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']),
            'variants' => BagProductVariant::query()
                ->where('status', 'active')
                ->with(['product:id,code,name,size_label', 'color:id,name'])
                ->orderBy('variant_code')
                ->get(['id', 'bag_product_id', 'variant_code', 'name', 'color_id', 'selling_price', 'saleable_material_id']),
            'materials' => Material::query()->where('status', 'active')->where('inventory_bucket', 'finished_bag')->orderBy('code')->get(['id', 'code', 'name', 'unit_of_measurement_id']),
            'products' => BagProduct::query()->where('status', 'active')->orderBy('code')->get(['id', 'code', 'name', 'size_label', 'selling_price']),
            'units' => UnitOfMeasurement::query()->where('status', 'active')->orderBy('code')->get(['id', 'code', 'name']),
        ];
    }

    /** @return list<array{label: string, href?: string}> */
    private function crumbs(string $tail): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Sales', 'href' => route('erp.sales')],
            ['label' => $tail],
        ];
    }
}