<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\Purchases\SupplierPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierPaymentController extends Controller
{
    public function __construct(
        private readonly SupplierPaymentService $payments,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'purchases');

        $payments = SupplierPayment::query()
            ->with('supplier')
            ->orderByDesc('payment_date')
            ->paginate(15);

        return Inertia::render('Purchases/Payments/Index', [
            'pageTitle' => 'Supplier payments',
            'breadcrumbs' => $this->crumbs('Payments'),
            'payments' => $payments,
            'canManage' => $request->user()?->can('purchases.manage') === true,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('managePurchases');

        $supplierId = $request->integer('supplier_id');
        $invoices = PurchaseInvoice::query()
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->whereIn('status', ['posted', 'partially_paid'])
            ->orderBy('invoice_date')
            ->get();

        return Inertia::render('Purchases/Payments/Form', [
            'pageTitle' => 'Record supplier payment',
            'breadcrumbs' => $this->crumbs('New payment'),
            'suppliers' => Supplier::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']),
            'openInvoices' => $invoices,
            'paymentMethods' => config('purchases.payment_methods'),
            'preselectedSupplierId' => $supplierId ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('managePurchases');

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.purchase_invoice_id' => ['required', 'exists:purchase_invoices,id'],
            'allocations.*.amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $supplier = Supplier::query()->findOrFail($validated['supplier_id']);
        $header = collect($validated)->except('allocations')->all();

        $payment = $this->payments->record($supplier, $header, $validated['allocations'], $request->user());

        return redirect()->route('purchases.payments.index')->with('success', "Payment {$payment->payment_number} recorded.");
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