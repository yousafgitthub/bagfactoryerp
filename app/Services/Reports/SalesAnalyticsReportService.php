<?php

namespace App\Services\Reports;

use App\Models\Customer;
use App\Models\CustomerReceipt;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Services\Reports\Concerns\PaginatesReportRows;
use Illuminate\Support\Facades\DB;

class SalesAnalyticsReportService
{
    use PaginatesReportRows;

    public function customerWise(ReportFilters $filters): ReportResult
    {
        $rows = DB::table('sales_invoices as i')
            ->join('customers as c', 'c.id', '=', 'i.customer_id')
            ->where('i.status', '!=', 'draft')
            ->when($filters->from, fn ($q) => $q->whereDate('i.invoice_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('i.invoice_date', '<=', $filters->to))
            ->when($filters->customerId, fn ($q) => $q->where('i.customer_id', $filters->customerId))
            ->selectRaw('c.code, c.name, COUNT(*) as invoices, SUM(i.grand_total) as revenue, SUM(i.cogs_total) as cogs')
            ->groupBy('c.id', 'c.code', 'c.name')
            ->orderByDesc('revenue')
            ->get();

        $all = $rows->map(fn ($r) => [
            'customer' => $r->code.' '.$r->name,
            'invoices' => (int) $r->invoices,
            'revenue' => round((float) $r->revenue, 2),
            'cogs' => round((float) $r->cogs, 2),
            'gross_profit' => round((float) $r->revenue - (float) $r->cogs, 2),
        ])->all();

        return new ReportResult(
            title: 'Customer-wise sales',
            description: 'Invoice revenue and posted COGS by customer.',
            columns: [
                ['key' => 'customer', 'label' => 'Customer', 'sortable' => true],
                ['key' => 'invoices', 'label' => 'Invoices', 'sortable' => true],
                ['key' => 'revenue', 'label' => 'Revenue', 'sortable' => true],
                ['key' => 'cogs', 'label' => 'COGS', 'sortable' => true],
                ['key' => 'gross_profit', 'label' => 'Gross profit', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function productWise(ReportFilters $filters): ReportResult
    {
        $rows = DB::table('sales_invoice_lines as l')
            ->join('sales_invoices as i', 'i.id', '=', 'l.sales_invoice_id')
            ->join('materials as m', 'm.id', '=', 'l.material_id')
            ->where('i.status', '!=', 'draft')
            ->when($filters->from, fn ($q) => $q->whereDate('i.invoice_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('i.invoice_date', '<=', $filters->to))
            ->selectRaw('m.code, m.name, SUM(l.quantity) as qty, SUM(l.line_total) as revenue')
            ->groupBy('m.id', 'm.code', 'm.name')
            ->orderByDesc('revenue')
            ->get();

        $all = $rows->map(fn ($r) => [
            'product' => $r->code.' '.$r->name,
            'quantity' => round((float) $r->qty, 2),
            'revenue' => round((float) $r->revenue, 2),
        ])->all();

        return new ReportResult(
            title: 'Product-wise sales',
            description: 'Finished goods invoice lines.',
            columns: [
                ['key' => 'product', 'label' => 'Product', 'sortable' => true],
                ['key' => 'quantity', 'label' => 'Quantity', 'sortable' => true],
                ['key' => 'revenue', 'label' => 'Revenue', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function variantWise(ReportFilters $filters): ReportResult
    {
        $rows = DB::table('sales_invoice_lines as l')
            ->join('sales_invoices as i', 'i.id', '=', 'l.sales_invoice_id')
            ->leftJoin('material_variants as v', 'v.id', '=', 'l.material_variant_id')
            ->leftJoin('colors as c', 'c.id', '=', 'v.color_id')
            ->where('i.status', '!=', 'draft')
            ->when($filters->from, fn ($q) => $q->whereDate('i.invoice_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('i.invoice_date', '<=', $filters->to))
            ->selectRaw("COALESCE(c.name, v.name, 'Standard') as color")
            ->selectRaw('SUM(l.quantity) as qty, SUM(l.line_total) as revenue')
            ->groupBy('color')
            ->orderByDesc('revenue')
            ->get();

        $all = $rows->map(fn ($r) => [
            'color' => $r->color,
            'quantity' => round((float) $r->qty, 2),
            'revenue' => round((float) $r->revenue, 2),
        ])->all();

        return new ReportResult(
            title: 'Color / variant-wise sales',
            description: 'Sales grouped by variant/color.',
            columns: [
                ['key' => 'color', 'label' => 'Color / variant', 'sortable' => true],
                ['key' => 'quantity', 'label' => 'Quantity', 'sortable' => true],
                ['key' => 'revenue', 'label' => 'Revenue', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function receipts(ReportFilters $filters): ReportResult
    {
        $all = CustomerReceipt::query()
            ->with('customer:id,code,name')
            ->where('status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('receipt_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('receipt_date', '<=', $filters->to))
            ->orderByDesc('receipt_date')
            ->get()
            ->map(fn ($r) => [
                'date' => $r->receipt_date?->toDateString(),
                'reference' => $r->receipt_number,
                'customer' => $r->customer?->code.' '.$r->customer?->name,
                'amount' => round((float) $r->amount, 2),
            ])->all();

        return new ReportResult(
            title: 'Sales receipts',
            description: 'Customer collections (cash inflow, not revenue).',
            columns: [
                ['key' => 'date', 'label' => 'Date', 'sortable' => true],
                ['key' => 'reference', 'label' => 'Receipt #', 'sortable' => true],
                ['key' => 'customer', 'label' => 'Customer', 'sortable' => true],
                ['key' => 'amount', 'label' => 'Amount', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            summary: ['receipts_total' => round(array_sum(array_column($all, 'amount')), 2)],
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function outstanding(ReportFilters $filters): ReportResult
    {
        $all = Customer::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'customer' => $c->code.' '.$c->name,
                'ledger_balance' => round((float) $c->current_balance, 2),
            ])->all();

        $invoiceOutstanding = (float) (SalesInvoice::query()
            ->whereIn('status', ['posted', 'partially_paid'])
            ->selectRaw('COALESCE(SUM(CASE WHEN grand_total > amount_paid THEN grand_total - amount_paid ELSE 0 END), 0) as outstanding')
            ->first()
            ->outstanding ?? 0);

        return new ReportResult(
            title: 'Outstanding receivables',
            description: 'Customer current_balance vs open invoices.',
            columns: [
                ['key' => 'customer', 'label' => 'Customer', 'sortable' => true],
                ['key' => 'ledger_balance', 'label' => 'Balance', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            summary: [
                'customer_ledger_total' => round(array_sum(array_column($all, 'ledger_balance')), 2),
                'open_invoice_total' => round($invoiceOutstanding, 2),
            ],
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function returns(ReportFilters $filters): ReportResult
    {
        $all = SalesReturn::query()
            ->with('customer:id,code,name')
            ->where('status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('return_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('return_date', '<=', $filters->to))
            ->orderByDesc('return_date')
            ->get()
            ->map(fn ($r) => [
                'date' => $r->return_date?->toDateString(),
                'reference' => $r->return_number,
                'customer' => $r->customer?->code.' '.$r->customer?->name,
                'amount' => round((float) $r->grand_total, 2),
            ])->all();

        return new ReportResult(
            title: 'Sales returns',
            description: 'Posted sales returns.',
            columns: [
                ['key' => 'date', 'label' => 'Date', 'sortable' => true],
                ['key' => 'reference', 'label' => 'Return #', 'sortable' => true],
                ['key' => 'customer', 'label' => 'Customer', 'sortable' => true],
                ['key' => 'amount', 'label' => 'Amount', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }
}