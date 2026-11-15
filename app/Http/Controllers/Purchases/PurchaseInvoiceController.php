<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\Purchases\PurchaseInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseInvoiceController extends Controller
{
    public function __construct(
        private readonly PurchaseInvoiceService $invoices,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'purchases');

        $invoices = PurchaseInvoice::query()
            ->with('supplier')
            ->orderByDesc('invoice_date')
            ->paginate(15);

        return Inertia::render('Purchases/Invoices/Index', [
            'pageTitle' => 'Purchase invoices',
            'breadcrumbs' => $this->crumbs('Invoices'),
            'invoices' => $invoices,
            'canManage' => $request->user()?->can('purchases.manage') === true,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('managePurchases');

        $purchaseOrder = null;
        if ($poId = $request->integer('purchase_order_id')) {
            $purchaseOrder = PurchaseOrder::query()->with('lines.material', 'supplier')->findOrFail($poId);
        }

        return Inertia::render('Purchases/Invoices/Form', [
            'pageTitle' => 'New purchase invoice',
            'breadcrumbs' => $this->crumbs('New invoice'),
            'invoice' => null,
            'purchaseOrder' => $purchaseOrder,
            'suppliers' => Supplier::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('managePurchases');

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:64'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_order_line_id' => ['nullable', 'integer'],
            'lines.*.goods_receipt_line_id' => ['nullable', 'integer'],
            'lines.*.material_id' => ['required', 'exists:materials,id'],
            'lines.*.material_variant_id' => ['nullable', 'integer'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'gte:0'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'gte:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'gte:0'],
        ]);

        $header = collect($validated)->except('lines')->all();
        $invoice = $this->invoices->createDraft($header, $validated['lines'], $request->user());

        return redirect()->route('purchases.invoices.show', $invoice)->with('success', 'Invoice saved as draft.');
    }

    public function show(Request $request, PurchaseInvoice $purchaseInvoice): Response
    {
        $this->authorize('viewModule', 'purchases');

        $purchaseInvoice->load(['supplier', 'lines.material', 'purchaseOrder']);

        return Inertia::render('Purchases/Invoices/Show', [
            'pageTitle' => $purchaseInvoice->invoice_number,
            'breadcrumbs' => $this->crumbs($purchaseInvoice->invoice_number),
            'invoice' => $purchaseInvoice,
            'canManage' => $request->user()?->can('purchases.manage') === true,
        ]);
    }

    public function post(Request $request, PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        $this->authorize('managePurchases');

        $this->invoices->post($purchaseInvoice, $request->user());

        return back()->with('success', 'Invoice posted to supplier ledger (no stock movement).');
    }

    public function createFromOrder(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('managePurchases');

        $data = $request->validate([
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_order_line_id' => ['required', 'integer'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.goods_receipt_line_id' => ['nullable', 'integer'],
        ]);

        $header = collect($data)->except('lines')->all();
        $invoice = $this->invoices->createFromPurchaseOrder($purchaseOrder, $header, $data['lines'], $request->user());

        return redirect()->route('purchases.invoices.show', $invoice)->with('success', 'Draft invoice created from PO.');
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