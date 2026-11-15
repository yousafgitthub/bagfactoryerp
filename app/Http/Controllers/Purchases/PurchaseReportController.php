<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseReportController extends Controller
{
    public function outstanding(Request $request): Response
    {
        $this->authorize('viewModule', 'purchases');

        $invoices = PurchaseInvoice::query()
            ->with('supplier')
            ->whereIn('status', ['posted', 'partially_paid'])
            ->orderBy('due_date')
            ->get()
            ->map(fn (PurchaseInvoice $inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'supplier' => $inv->supplier?->only(['id', 'code', 'name']),
                'invoice_date' => $inv->invoice_date?->toDateString(),
                'due_date' => $inv->due_date?->toDateString(),
                'grand_total' => $inv->grand_total,
                'amount_paid' => $inv->amount_paid,
                'outstanding' => $inv->outstandingAmount(),
                'status' => $inv->status,
            ]);

        $supplierBalances = Supplier::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'current_balance']);

        return Inertia::render('Purchases/Reports/Outstanding', [
            'pageTitle' => 'Outstanding payables',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Purchases', 'href' => route('erp.purchases')],
                ['label' => 'Outstanding'],
            ],
            'invoices' => $invoices,
            'supplierBalances' => $supplierBalances,
        ]);
    }
}