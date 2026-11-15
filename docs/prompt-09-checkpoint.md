# Prompt 09 checkpoint — Sales and customers

## Completed

- **Customers:** code, contact fields, opening balance, ledger, CRUD UI (`/customers/*`).
- **Sales documents:** sales orders, sales invoices, customer receipts, sales returns.
- **Lines:** product variant → saleable material, size/color labels, qty, price, discount, tax.
- **Posting (invoice):** deducts finished-goods via `sales_issue`; COGS from inventory **WAC** (`cogs_total`); customer receivable (debit ledger); cash sales auto-receipt on post.
- **Returns:** condition `saleable` / `damaged`; posted stock to `sales_return_saleable` or `sales_return_rejected`; customer credit.
- **UI:** sales hub, customer hub, orders, invoices (incl. print), receipts, returns, outstanding receivables, sales summary report.
- **Oversell:** blocked unless `SALES_ALLOW_OVERSELL=true` **and** permission `sales.oversell`.

## Stock / costing alignment (Prompt 08)

- Purchases: stock on GRN, WAC on receipt.
- Sales: stock and COGS on **posted sales invoice** using the same WAC balances.

## Migrations

- `database/migrations/2026_11_15_200000_create_sales_tables.php` — additive only.

## Tests (PHP 8.5.4, SQLite in-memory)

```text
php artisan test --filter=SalesWorkflowTest
```

| Test | Result |
|------|--------|
| Posted invoice deducts stock and records WAC COGS | Pass |
| Credit partial payment + oversell guard | Pass |
| Saleable return increases stock | Pass |

Full suite (PHP 8.5.4): **73 passed**, 214 assertions — `D:\laragon\bin\php\php-8.5.4-Win32-vs17-x64\php.exe artisan test` on 2026-11-15.

## Config

- `config/sales.php` — document prefixes, sale types, return conditions, company print block, `allow_oversell`.
- `config/erp.php` — `sales.oversell` extra permission; factory-manager `sales.manage` / `customers.manage`.

## Not in scope (Prompt 10)

- General ledger / expense modules, cash/bank accounting integration beyond customer ledger.

## Known limits

- Sales return with mixed saleable + damaged lines creates two inventory transactions; header links the first only.
- No duplicate-post guard tests beyond service exceptions.