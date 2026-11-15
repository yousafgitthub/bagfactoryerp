<?php

namespace App\Services\Reports;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\Reports\Concerns\PaginatesReportRows;
use Illuminate\Support\Facades\DB;

class PurchaseReportService
{
    use PaginatesReportRows;

    public function supplierWise(ReportFilters $filters): ReportResult
    {
        $rows = DB::table('purchase_invoices as i')
            ->join('suppliers as s', 's.id', '=', 'i.supplier_id')
            ->where('i.status', '!=', 'draft')
            ->when($filters->from, fn ($q) => $q->whereDate('i.invoice_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('i.invoice_date', '<=', $filters->to))
            ->when($filters->supplierId, fn ($q) => $q->where('i.supplier_id', $filters->supplierId))
            ->selectRaw('s.code, s.name, COUNT(*) as invoice_count, SUM(i.grand_total) as total')
            ->groupBy('s.id', 's.code', 's.name')
            ->orderByDesc('total')
            ->get();

        $all = $rows->map(fn ($r) => [
            'supplier' => $r->code.' '.$r->name,
            'invoices' => (int) $r->invoice_count,
            'purchases' => round((float) $r->total, 2),
        ])->all();

        return new ReportResult(
            title: 'Supplier-wise purchases',
            description: 'Posted purchase invoices grouped by supplier.',
            columns: [
                ['key' => 'supplier', 'label' => 'Supplier', 'sortable' => true],
                ['key' => 'invoices', 'label' => 'Invoices', 'sortable' => true],
                ['key' => 'purchases', 'label' => 'Purchase amount', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function materialWise(ReportFilters $filters): ReportResult
    {
        $rows = DB::table('purchase_invoice_lines as l')
            ->join('purchase_invoices as i', 'i.id', '=', 'l.purchase_invoice_id')
            ->join('materials as m', 'm.id', '=', 'l.material_id')
            ->where('i.status', '!=', 'draft')
            ->when($filters->from, fn ($q) => $q->whereDate('i.invoice_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('i.invoice_date', '<=', $filters->to))
            ->selectRaw('m.code, m.name, SUM(l.quantity) as qty, SUM(l.line_total) as amount')
            ->groupBy('m.id', 'm.code', 'm.name')
            ->orderByDesc('amount')
            ->get();

        $all = $rows->map(fn ($r) => [
            'material' => $r->code.' '.$r->name,
            'quantity' => round((float) $r->qty, 4),
            'amount' => round((float) $r->amount, 2),
        ])->all();

        return new ReportResult(
            title: 'Material-wise purchases',
            description: 'Invoice lines — inventory purchases, not COGS.',
            columns: [
                ['key' => 'material', 'label' => 'Material', 'sortable' => true],
                ['key' => 'quantity', 'label' => 'Quantity', 'sortable' => true],
                ['key' => 'amount', 'label' => 'Amount', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function payments(ReportFilters $filters): ReportResult
    {
        $all = SupplierPayment::query()
            ->with('supplier:id,code,name')
            ->where('status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('payment_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('payment_date', '<=', $filters->to))
            ->orderByDesc('payment_date')
            ->get()
            ->map(fn ($p) => [
                'date' => $p->payment_date?->toDateString(),
                'reference' => $p->payment_number,
                'supplier' => $p->supplier?->code.' '.$p->supplier?->name,
                'amount' => round((float) $p->amount, 2),
                'method' => $p->payment_method,
            ])->all();

        return new ReportResult(
            title: 'Purchase payments',
            description: 'Cash/AP settlements (cash flow, not expense duplication).',
            columns: [
                ['key' => 'date', 'label' => 'Date', 'sortable' => true],
                ['key' => 'reference', 'label' => 'Payment #', 'sortable' => true],
                ['key' => 'supplier', 'label' => 'Supplier', 'sortable' => true],
                ['key' => 'amount', 'label' => 'Amount', 'sortable' => true],
                ['key' => 'method', 'label' => 'Method', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            summary: ['payments_total' => round(array_sum(array_column($all, 'amount')), 2)],
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function supplierOutstanding(ReportFilters $filters): ReportResult
    {
        $all = Supplier::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(fn ($s) => [
                'supplier' => $s->code.' '.$s->name,
                'ledger_balance' => round((float) $s->current_balance, 2),
            ])->all();

        $invoiceOutstanding = (float) (PurchaseInvoice::query()
            ->whereIn('status', ['posted', 'partially_paid'])
            ->selectRaw('COALESCE(SUM(CASE WHEN grand_total > amount_paid THEN grand_total - amount_paid ELSE 0 END), 0) as outstanding')
            ->first()
            ->outstanding ?? 0);

        return new ReportResult(
            title: 'Supplier outstanding balances',
            description: 'Supplier current_balance reconciles to supplier ledger.',
            columns: [
                ['key' => 'supplier', 'label' => 'Supplier', 'sortable' => true],
                ['key' => 'ledger_balance', 'label' => 'Balance', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            summary: [
                'supplier_ledger_total' => round(array_sum(array_column($all, 'ledger_balance')), 2),
                'open_invoice_total' => round($invoiceOutstanding, 2),
            ],
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function returns(ReportFilters $filters): ReportResult
    {
        $all = PurchaseReturn::query()
            ->with('supplier:id,code,name')
            ->where('status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('return_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('return_date', '<=', $filters->to))
            ->orderByDesc('return_date')
            ->get()
            ->map(fn ($r) => [
                'date' => $r->return_date?->toDateString(),
                'reference' => $r->return_number,
                'supplier' => $r->supplier?->code.' '.$r->supplier?->name,
                'amount' => round((float) $r->grand_total, 2),
            ])->all();

        return new ReportResult(
            title: 'Purchase returns',
            description: 'Posted returns reduce payables and inventory.',
            columns: [
                ['key' => 'date', 'label' => 'Date', 'sortable' => true],
                ['key' => 'reference', 'label' => 'Return #', 'sortable' => true],
                ['key' => 'supplier', 'label' => 'Supplier', 'sortable' => true],
                ['key' => 'amount', 'label' => 'Amount', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }
}