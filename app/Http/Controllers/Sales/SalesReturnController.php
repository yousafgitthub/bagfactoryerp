<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Material;
use App\Models\SalesReturn;
use App\Models\Warehouse;
use App\Services\Sales\SalesReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesReturnController extends Controller
{
    public function __construct(
        private readonly SalesReturnService $returns,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'sales');

        $returns = SalesReturn::query()
            ->with('customer')
            ->orderByDesc('return_date')
            ->paginate(15);

        return Inertia::render('Sales/Returns/Index', [
            'pageTitle' => 'Sales returns',
            'breadcrumbs' => $this->crumbs('Returns'),
            'returns' => $returns,
            'canManage' => $request->user()?->can('sales.manage') === true,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('manageSales');

        return Inertia::render('Sales/Returns/Form', [
            'pageTitle' => 'New sales return',
            'breadcrumbs' => $this->crumbs('New return'),
            'customers' => Customer::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']),
            'warehouses' => Warehouse::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']),
            'materials' => Material::query()->where('inventory_bucket', 'finished_bag')->where('status', 'active')->get(['id', 'code', 'name']),
            'conditions' => config('sales.return_conditions'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manageSales');

        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'sales_invoice_id' => ['nullable', 'exists:sales_invoices,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'return_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.material_id' => ['required', 'exists:materials,id'],
            'lines.*.material_variant_id' => ['nullable', 'integer'],
            'lines.*.bag_product_variant_id' => ['nullable', 'exists:bag_product_variants,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.condition' => ['required', 'in:saleable,damaged'],
            'lines.*.unit_price' => ['required', 'numeric', 'gte:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'gte:0'],
            'lines.*.inspection_notes' => ['nullable', 'string'],
        ]);

        $header = collect($data)->except('lines')->all();
        $return = $this->returns->createDraft($header, $data['lines'], $request->user());

        return redirect()->route('sales.returns.show', $return)->with('success', 'Sales return draft created.');
    }

    public function show(Request $request, SalesReturn $salesReturn): Response
    {
        $this->authorize('viewModule', 'sales');

        $salesReturn->load(['customer', 'lines.material', 'salesInvoice']);

        return Inertia::render('Sales/Returns/Show', [
            'pageTitle' => $salesReturn->return_number,
            'breadcrumbs' => $this->crumbs($salesReturn->return_number),
            'salesReturn' => $salesReturn,
            'canManage' => $request->user()?->can('sales.manage') === true,
        ]);
    }

    public function post(Request $request, SalesReturn $salesReturn): RedirectResponse
    {
        $this->authorize('manageSales');

        $this->returns->post($salesReturn, $request->user());

        return back()->with('success', 'Sales return posted — stock and customer credit updated.');
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