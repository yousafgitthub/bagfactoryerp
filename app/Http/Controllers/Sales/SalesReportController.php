<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesReportController extends Controller
{
    public function outstanding(Request $request): Response
    {
        $this->authorize('viewModule', 'sales');

        $invoices = SalesInvoice::query()
            ->with('customer')
            ->whereIn('status', ['posted', 'partially_paid'])
            ->orderBy('due_date')
            ->get()
            ->map(fn (SalesInvoice $inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'customer' => $inv->customer?->only(['id', 'code', 'name']),
                'invoice_date' => $inv->invoice_date?->toDateString(),
                'due_date' => $inv->due_date?->toDateString(),
                'grand_total' => $inv->grand_total,
                'amount_paid' => $inv->amount_paid,
                'outstanding' => $inv->outstandingAmount(),
                'status' => $inv->status,
            ]);

        $customerBalances = Customer::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'current_balance']);

        return Inertia::render('Sales/Reports/Outstanding', [
            'pageTitle' => 'Outstanding receivables',
            'breadcrumbs' => $this->crumbs('Outstanding'),
            'invoices' => $invoices,
            'customerBalances' => $customerBalances,
        ]);
    }

    public function summary(Request $request): Response
    {
        $this->authorize('viewModule', 'sales');

        $from = $request->string('from')->toString() ?: now()->startOfMonth()->toDateString();
        $to = $request->string('to')->toString() ?: now()->toDateString();

        $rows = SalesInvoice::query()
            ->where('status', '!=', 'draft')
            ->whereBetween('invoice_date', [$from, $to])
            ->selectRaw('COUNT(*) as invoice_count, SUM(grand_total) as revenue, SUM(tax_total) as tax, SUM(cogs_total) as cogs')
            ->first();

        return Inertia::render('Sales/Reports/Summary', [
            'pageTitle' => 'Sales summary',
            'breadcrumbs' => $this->crumbs('Summary'),
            'filters' => ['from' => $from, 'to' => $to],
            'summary' => [
                'invoice_count' => (int) ($rows->invoice_count ?? 0),
                'revenue' => (float) ($rows->revenue ?? 0),
                'tax' => (float) ($rows->tax ?? 0),
                'cogs' => (float) ($rows->cogs ?? 0),
                'gross_margin' => round((float) ($rows->revenue ?? 0) - (float) ($rows->cogs ?? 0), 2),
            ],
        ]);
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