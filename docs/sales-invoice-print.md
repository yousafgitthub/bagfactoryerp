# Sales invoice print

## Entry points

- **Invoice detail** — **Print Invoice** opens `sales.invoices.print` in a new tab.
- **Invoice listing** — row **Print** action opens the same route for that invoice.

Route: `GET /sales/invoices/{salesInvoice}/print` (`sales.invoices.print`). Same `viewModule` sales permission as invoice show.

## Print view

Inertia page `Sales/Invoices/Print` — standalone layout (no ERP sidebar). On-screen **Print** calls `window.print()`; toolbar is hidden with `@media print`. Page size A4, 12mm margins; long line lists repeat table headers and avoid breaking rows mid-page.

### Data shown

| Section | Source |
|--------|--------|
| Company name, address, phone, email | `website_settings` with fallback to `config('sales.company')` |
| Customer, lines, totals, paid, outstanding | `SalesInvoice` model (same stored totals as detail) |
| Payment status | `config('sales.invoice_statuses')` |
| Sale type | `config('sales.sale_types')` (not a per-invoice payment method field) |
| Notes | `sales_invoices.notes` when set |

### Omitted (no schema / settings field)

- Company logo URL
- Company tax registration
- Invoice-level `payment_method` (receipts store method on `customer_receipts` only)

## Tests

`tests/Feature/Sales/SalesInvoicePrintTest.php` — authorized print, guest redirect, forbidden without sales access, partial payment totals.

## Manual check

Use browser print preview (A4). Cloud agents do not capture print preview screenshots here; verify locally and save `media/sales-invoice-print.png` if needed.