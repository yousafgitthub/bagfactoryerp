<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerReceipt;
use App\Models\SalesInvoice;
use App\Services\Sales\CustomerReceiptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerReceiptController extends Controller
{
    public function __construct(
        private readonly CustomerReceiptService $receipts,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'sales');

        $receipts = CustomerReceipt::query()
            ->with('customer')
            ->orderByDesc('receipt_date')
            ->paginate(15);

        return Inertia::render('Sales/Receipts/Index', [
            'pageTitle' => 'Customer receipts',
            'breadcrumbs' => $this->crumbs('Receipts'),
            'receipts' => $receipts,
            'canManage' => $request->user()?->can('sales.manage') === true,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('manageSales');

        $customerId = $request->integer('customer_id');
        $openInvoices = SalesInvoice::query()
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->whereIn('status', ['posted', 'partially_paid'])
            ->orderBy('invoice_date')
            ->get();

        return Inertia::render('Sales/Receipts/Form', [
            'pageTitle' => 'New receipt',
            'breadcrumbs' => $this->crumbs('New receipt'),
            'customers' => Customer::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']),
            'openInvoices' => $openInvoices,
            'paymentMethods' => config('sales.payment_methods'),
            'prefillCustomerId' => $customerId ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manageSales');

        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'receipt_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string'],
            'reference' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.sales_invoice_id' => ['required', 'exists:sales_invoices,id'],
            'allocations.*.amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $customer = Customer::query()->findOrFail($data['customer_id']);
        $header = collect($data)->only(['receipt_date', 'amount', 'payment_method', 'reference', 'notes'])->all();

        $this->receipts->record($customer, $header, $data['allocations'], $request->user());

        return redirect()->route('sales.receipts.index')->with('success', 'Customer receipt recorded.');
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