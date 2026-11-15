# Prompt 11 checkpoint — Reports and analytics

## Completed

- **Central hub:** `/reports` catalog (`Reports/Hub.vue`) grouped by production, inventory, payroll, purchases, sales, financial.
- **41 analytics reports** via `config/reports.php` + domain services under `app/Services/Reports/`.
- **Unified UI:** `Reports/Show.vue` with date range, granularity (day/month), search, sort, pagination; summary KPIs.
- **Export:** CSV download (Excel-compatible); print/PDF via `Reports/Print.vue` (browser print-to-PDF).
- **Ledger reconciliation:** Reports read posted batches, inventory balances/ledger, payroll records, purchase/sales documents, GL/treasury — no hardcoded figures. Gross profit from invoices vs net profit from GL is documented in `financial-gross-net-profit`.
- **Legacy module reports** (Dana/Bag costing, accounting trial balance pages) remain; hub is the single entry for Prompt 11 catalog.

## Key files

- `config/reports.php`
- `app/Http/Controllers/Reports/ReportsHubController.php`, `ErpReportController.php`
- `app/Services/Reports/*ReportService.php`, `ReportRunner.php`
- `resources/js/Pages/Reports/Hub.vue`, `Show.vue`, `Print.vue`
- `routes/web.php` — `erp.reports`, `erp.reports.show`, `erp.reports.export`

## Tests (PHP 8.5.4, SQLite in-memory)

```text
D:\laragon\bin\php\php-8.5.4-Win32-vs17-x64\php.exe artisan test --filter=ReportsReconciliationTest
D:\laragon\bin\php\php-8.5.4-Win32-vs17-x64\php.exe artisan test
```

| Test | Result |
|------|--------|
| Reports hub permission | Pass |
| Inventory valuation Inertia page | Pass |
| Valuation sum = balances.total_value | Pass |
| Inventory balance/ledger reconciliation empty | Pass |
| CSV export | Pass |

**Full suite:** 82 passed (was 77; +5 reports).

## Not in scope

- Prompt 12 (final testing and audit).
- Dompdf/server-side PDF library (print view used instead).

## Notes

- Production employee report uses `employee_production_assignments` (not operator name strings).
- Inventory purchase value is separate from COGS on sales; financial reports label sources to avoid double counting.