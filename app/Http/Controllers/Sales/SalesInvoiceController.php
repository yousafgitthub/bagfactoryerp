<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\BagProductVariant;
use App\Models\Customer;
use App\Models\Material;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\UnitOfMeasurement;
use App\Models\Warehouse;
use App\Models\WebsiteSetting;
use App\Services\Sales\SalesInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesInvoiceController extends Controller
{
    public function __construct(
        private readonly SalesInvoiceService $invoices,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'sales');

        $invoices = SalesInvoice::query()
            ->with('customer')
            ->orderByDesc('invoice_date')
            ->paginate(15);

        return Inertia::render('Sales/Invoices/Index', [
            'pageTitle' => 'Sales invoices',
            'breadcrumbs' => $this->crumbs('Invoices'),
            'invoices' => $invoices,
            'canManage' => $request->user()?->can('sales.manage') === true,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('manageSales');

        $salesOrder = null;
        if ($soId = $request->integer('sales_order_id')) {
            $salesOrder = SalesOrder::query()->with('lines.material', 'customer')->findOrFail($soId);
        }

        return Inertia::render('Sales/Invoices/Form', [
            'pageTitle' => 'New sales invoice',
            'breadcrumbs' => $this->crumbs('New invoice'),
            'invoice' => null,
            'salesOrder' => $salesOrder,
            'lookups' => $this->lookups(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manageSales');

        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'sales_order_id' => ['nullable', 'exists:sales_orders,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'sale_type' => ['required', 'in:cash,credit'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.sales_order_line_id' => ['nullable', 'integer'],
            'lines.*.bag_product_variant_id' => ['nullable', 'exists:bag_product_variants,id'],
            'lines.*.bag_product_id' => ['nullable', 'exists:bag_products,id'],
            'lines.*.material_id' => ['nullable', 'exists:materials,id'],
            'lines.*.material_variant_id' => ['nullable', 'integer'],
            'lines.*.unit_of_measurement_id' => ['required', 'exists:units_of_measurement,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'gte:0'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'gte:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'gte:0'],
        ]);

        $header = collect($validated)->except('lines')->all();
        $invoice = $this->invoices->createDraft($header, $validated['lines'], $request->user());

        return redirect()->route('sales.invoices.show', $invoice)->with('success', 'Invoice saved as draft.');
    }

    public function show(Request $request, SalesInvoice $salesInvoice): Response
    {
        $this->authorize('viewModule', 'sales');

        $salesInvoice->load(['customer', 'lines.material', 'lines.bagProduct', 'salesOrder', 'warehouse']);

        return Inertia::render('Sales/Invoices/Show', [
            'pageTitle' => $salesInvoice->invoice_number,
            'breadcrumbs' => $this->crumbs($salesInvoice->invoice_number),
            'invoice' => $salesInvoice,
            'canManage' => $request->user()?->can('sales.manage') === true,
            'printUrl' => route('sales.invoices.print', $salesInvoice),
        ]);
    }

    public function print(SalesInvoice $salesInvoice): Response
    {
        $this->authorize('viewModule', 'sales');

        $salesInvoice->load([
            'customer',
            'warehouse',
            'lines.material',
            'lines.bagProduct',
            'lines.bagProductVariant',
            'lines.unitOfMeasurement',
        ]);

        return Inertia::render('Sales/Invoices/Print', [
            'pageTitle' => 'Print '.$salesInvoice->invoice_number,
            'invoice' => $salesInvoice,
            'company' => $this->companyForPrint(),
            'outstanding' => $salesInvoice->outstandingAmount(),
            'statusLabel' => config('sales.invoice_statuses.'.$salesInvoice->status, $salesInvoice->status),
            'saleTypeLabel' => config('sales.sale_types.'.$salesInvoice->sale_type, $salesInvoice->sale_type),
        ]);
    }

    public function post(Request $request, SalesInvoice $salesInvoice): RedirectResponse
    {
        $this->authorize('manageSales');

        $cash = $request->validate([
            'payment_method' => ['nullable', 'string'],
            'reference' => ['nullable', 'string'],
        ]);

        $this->invoices->post($salesInvoice, $request->user(), $cash);

        return back()->with('success', 'Invoice posted — stock deducted and receivable recorded.');
    }

    /** @return array<string, string|null> */
    private function companyForPrint(): array
    {
        $settings = WebsiteSetting::current();
        $fallback = config('sales.company');

        return [
            'name' => $settings->company_name ?: ($fallback['name'] ?? null),
            'address' => $settings->address ?: ($fallback['address'] ?? null),
            'phone' => $settings->phone ?: ($fallback['phone'] ?? null),
            'email' => $settings->email,
            'logo_url' => null,
            'tax_registration' => null,
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

    private function lookups(): array
    {
        return [
            'customers' => Customer::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']),
            'warehouses' => Warehouse::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']),
            'variants' => BagProductVariant::query()->where('status', 'active')->with('product:id,code,name,size_label')->get(['id', 'bag_product_id', 'variant_code', 'name', 'selling_price', 'saleable_material_id']),
            'materials' => Material::query()->where('status', 'active')->where('inventory_bucket', 'finished_bag')->get(['id', 'code', 'name', 'unit_of_measurement_id']),
            'units' => UnitOfMeasurement::query()->where('status', 'active')->get(['id', 'code', 'name']),
        ];
    }
}