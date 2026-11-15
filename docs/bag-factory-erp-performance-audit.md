---
cursor:
  subagentId: "bc-6981e542-8f0d-5a12-96bb-b514420f0f36"
---

# Bag Factory ERP performance audit

Measured on 15 Nov 2026 against branch `cursor/final-testing-audit-017c` (HEAD `845016f`), then remeasured after the fixes in this change.

**Harness.** SQLite `:memory:` via `php artisan test` (PHP 8.5.4). `DB::enableQueryLog()` around each HTTP GET and around each service call. Seed for the timed run: 400 sales invoices, 400 purchase invoices, 40 GL accounts with one posted journal line each, 30 posted Dana batches in the current month, 1 inventory balance. The same seed and the same counter were used before and after. HTTP wall time includes the test client; database time is the sum of logged query times.

**MySQL.** Read-only `SELECT COUNT(*)` and `EXPLAIN` on `bag_factory_erp`. No `ALTER`, no writes.

## 1. Slowest pages and endpoints

Ranked by query count on the seeded SQLite run, before changes. Database time is in milliseconds.

| Page | Queries | DB ms | Wall ms |
| --- | ---: | ---: | ---: |
| `GET /dashboard?period=month` | 186 | 9.33 | 39.22 |
| `GET /dashboard` (week, first request in the process) | 122 | 8.51 | 217.03 |
| `GET /dashboard?period=today` | 99 | 6.23 | 33.72 |
| `GET /accounting/reports/balance-sheet` and `/reports/analytics/financial-balance-sheet` | 82 | 2.98–3.64 | 36–38 |
| Trial balance and profit & loss (accounting routes and `/reports/analytics/financial-*`) | 41 | 1.83–2.39 | 26–49 |
| Sales outstanding report | 2 | 1.47 | 61.91 |
| Purchase outstanding report | 2 | 1.36 | 54.87 |
| Dana hub | 7 | 0.41 | 23.05 |
| Current stock | 6 | 0.27 | 22.48 |
| Bag hub | 5 | 0.35 | 21.80 |
| Sales invoices, purchase invoices, attendance monthly | 3 | 0.15–0.29 | 17–35 |
| Employees, attendance register | 2 | 0.13–0.15 | 17–20 |
| Movement history, payroll runs, stock ledger report | 1 | 0.04–0.16 | 17–21 |
| `GET /reports` | 0 | 0 | 23.79 |

The isolated dashboard service (`DashboardDataService::forUser`, period `month`) was 186 queries, 9.65 ms DB, 22.46 ms wall. Trial balance service: 41 queries, 2.34 ms DB, 12.58 ms. Balance sheet service: 82 queries, 4.88 ms DB, 25.87 ms. Sales outstanding service: 2 queries, 1.61 ms DB, 18.51 ms (hydrating every open invoice).

List pages that already paginate and eager-load (invoices, employees, attendance, ledger) stayed at 1–3 queries. They were not changed.

Production MySQL has no transactional volume yet: `users` 3, `gl_accounts` 16, `treasury_accounts` 2, roles/permissions, and empty `dana_batches`, `sales_invoices`, `purchase_invoices`, `inventory_ledger_entries`, `attendances`, `expenses`. `EXPLAIN` on those tables reports `rows: 1` and `type: ALL` or `range`. There is no production timing to rank beyond the SQLite counts above.

## 2. N+1 found and fixed

**Dashboard production trend.** For each calendar day the service ran `Schema::hasTable` for `dana_batches` and `shopping_bag_batches`, then `SUM(output_kg) WHERE date(posted_at) = ?`. November is 30 days. The logged `whereDate` sum ran **31** times (30 days plus today's summary card, which uses the same SQL text). `sqlite_master` existence checks for those two tables ran **34** and **32** times. Replaced with one existence check per table and one `SUM ... GROUP BY` calendar day. Today's card, the range total, and machine/waste sums stay separate single aggregates.

**Dashboard sales and expense chart.** Six `SUM(grand_total)` and six `SUM(amount)` (one pair per month), each preceded by an existence check (7 checks each, because the month summary card checks again). Replaced with one grouped sum per table.

**Stock summary cards.** Three `SUM(quantity) WHERE bucket = ?`. Replaced with one `GROUP BY bucket`.

**Active employees.** Counted in the summary cards and again in the payroll overview. Counted once.

**General ledger statements.** `FinancialReportService` ran `GlAccount::find()` once per aggregated account. With 40 accounts that was **40** point lookups on trial balance and on profit & loss. Balance sheet did the same and then called `profitAndLoss()`, which aggregated again and looked up every account again: **80** point lookups and **2** aggregate queries. Accounts are now loaded with one `whereIn`, and the balance sheet reuses that aggregate for retained profit. Totals use the same debit/credit formulas as before.

**Open invoice totals.** Not an N+1 (already one query). `SalesAnalyticsReportService::outstanding` and `PurchaseReportService::supplierOutstanding` did `select *` and summed `outstandingAmount()` in PHP. With ~320 posted sales invoices that query was **1.48 ms**; the purchase equivalent was **2.23 ms** on the first probe. Both are now `SUM(CASE WHEN grand_total > amount_paid THEN grand_total - amount_paid ELSE 0 END)`, which is `max(0, grand_total - amount_paid)` per invoice. A regression test expects **60.00** for invoices 100/40, 50/80, and a draft of 999. The seeded 400-invoice total matched the old PHP sum within 0.02.

No accessor or `$appends` N+1 exists on the models. Invoice, employee, attendance, stock, and ledger indexes already `with()` the relations they render.

## 3. Before/after query counts

Same seed, `DB::getQueryLog()`.

| Call | Queries before | Queries after | DB ms before | DB ms after |
| --- | ---: | ---: | ---: | ---: |
| Dashboard service, month | 186 | 54 | 9.65 | 3.47 |
| `GET /dashboard?period=month` (second dashboard hit in the process) | 186 | 39 | 9.33 | 4.04 |
| `GET /dashboard` week (first hit) | 122 | 59 | 8.51 | 4.06 |
| `GET /dashboard?period=today` | 99 | 39 | 6.23 | 4.32 |
| Dashboard service wall time, month | — | — | 22.46 ms wall | 8.49 ms wall |
| Trial balance service | 41 | 2 | 2.34 | 0.46 |
| `GET` trial balance (accounting and reports) | 41 | 2 | 1.83–2.39 | 0.39–0.56 |
| Profit & loss service path (HTTP) | 41 | 2 | 1.88–1.89 | 0.36–0.46 |
| Balance sheet service | 82 | 2 | 4.88 | 0.36 |
| Balance sheet HTTP | 82 | 2 | 2.98–3.64 | 0.35–0.42 |
| Balance sheet service wall | — | — | 25.87 ms | 3.04 ms |
| Sales outstanding HTTP | 2 | 2 | 1.47 | 0.35 |
| Sales outstanding service | 2 | 2 | 1.61 | 0.16 |
| Sales outstanding service wall | — | — | 18.51 ms | 0.62 ms |
| Purchase outstanding HTTP | 2 | 2 | 1.36 | 0.21 |
| Purchase outstanding service | 2 | 2 | 1.43 | 0.16 |
| Current stock, ledger, invoices, employees, attendance, payroll, Dana hub, bag hub, reports hub | unchanged (0–7) | unchanged | under 0.7 | under 0.7 |

The month HTTP count (39) is lower than the isolated service (54) because the week request in the same process had already paid `sqlite_master` / `pragma_table_xinfo` lookups. Both comparisons use the same warm-up on the before side (week request, then month request; service measured on its own). HTTP wall time for dashboard and reports stays about 20–60 ms and did not fall with the query count: database time was only a few milliseconds inside the test client. Service wall time did fall (dashboard 22.46 → 8.49 ms, balance sheet 25.87 → 3.04 ms, outstanding 18.51 → 0.62 ms).

Checked after the rewrite, on the same seed: Dana trend totals equal the old per-day `whereDate` sums; the six-month sales chart equals calendar-month sums of `grand_total`; stock cards equal per-bucket `SUM(quantity)`.

## 4. Slow queries and optimizations

The repeated statements, not a single long query, were the cost.

- `SUM(output_kg) WHERE strftime('%Y-%m-%d', posted_at) = ?` — 31 times, 2.52 ms combined on the month dashboard. Now one grouped sum.
- `sqlite_master` exists for `dana_batches` / `shopping_bag_batches` — 34 and 32 times. Now once per table per request (memoized on the service instance).
- `SUM(grand_total)` on `sales_invoices` by month — 7 times, 0.86 ms. Now one grouped sum, plus the existing month-summary `SUM` (that card still uses its own date filter).
- `select * from gl_accounts where id = ? limit 1` — 40 or 80 times, up to 5.26 ms. Now one `whereIn`.
- `select * from sales_invoices where status in (...)` — 1.44–1.48 ms for the open-invoice total. Now a `SUM` at 0.10 ms in the after log.
- `select * from purchase_invoices where status in (...)` — 1.53–2.23 ms. Same `SUM` change, 0.11 ms in the after log.

`EXPLAIN` on MySQL for the grouped Dana sum, the sales date sum, the outstanding `SUM`, the GL aggregate, the ledger movement aggregate, and the balance `GROUP BY` all show `rows: 1` because those tables are empty. Nothing to tune by plan until rows exist.

## 5. Missing or redundant indexes

No migration was added.

Gaps that `EXPLAIN` can see, and why they were left:

- `dana_batches` has `(status, production_date)` and `(machine_id, posted_at)`. A filter on `posted_at` alone cannot use the machine index (leftmost column is `machine_id`). `DATE(posted_at)` cannot use it either. `EXPLAIN` key is `null`, `rows: 1`.
- `sales_invoices` and `purchase_invoices` have `(customer_id|supplier_id, status)` and no `invoice_date` index. `EXPLAIN` for `invoice_date BETWEEN` is `type: ALL`, `rows: 1`.
- `inventory_ledger_entries` already has `movement_type` and `(material_id, occurred_at)` / `(warehouse_id, occurred_at)`. The consumption aggregate uses `movement_type` (`type: ref` on MySQL).
- `attendances` already has `attendance_date`. The monthly report `EXPLAIN` is `type: range` on that index.
- `expenses` already has `(expense_date, payment_status)`.
- `gl_journal_entries.entry_date` and `gl_journal_lines (gl_account_id, gl_journal_entry_id)` already exist. The aggregate joins entries by primary key (`eq_ref`).

An index on an empty table does not change these plans. Adding one would not have a measured before/after on this database.

## 6. Duplicate and unnecessary requests

**Fixed in the dashboard service.** Repeated existence checks, the per-day sums, the per-month sums, the three balance sums, and the second employee count. See section 2.

**Not a duplicate.** Dana hub's two `count(*) where status = ?` are posted vs draft (same SQL text, different bindings). Attendance monthly is one grouped query, not a query per employee. Sales and purchase lists are one page query plus one eager load.

**Frontend.** Vue pages call `router.get` only from filter and form actions (`Dashboard.vue`, report filters, HR indexes). No page issues a second Inertia or axios load of the same payload on mount. No Vue file was changed.

`GET /reports` runs 0 queries; the catalog is config.

## 7. Caching

No response cache, no `Cache::remember`, and Debugbar/Telescope were not enabled.

Inventory, payroll, and GL balances stay computed from the posted rows (or from `inventory_balances`, which the stock screens and valuation report already read). A cached balance would be wrong the moment a batch, invoice, or journal posts, and this pass did not add invalidation for that.

The dashboard memoizes `Schema::hasTable` for the lifetime of the service instance (one request). That is not a cross-request cache. It removed the repeated `sqlite_master` lookups inside the day loop.

Reference lookups that are already one small query (departments on the employee index, active employees on the attendance register) were left as queries.

## 8. Remaining bottlenecks

**Dashboard cards that never hit the real tables.** These names are checked and missed, so the figures stay 0. Pointing them at the live tables would change the numbers on screen, so this pass left them.

| Lookup in `DashboardDataService` | Table that actually exists |
| --- | --- |
| `shopping_bag_batches.output_pieces` | `bag_production_batches.good_quantity` |
| `shopping_bag_batch_wastes` | `bag_batch_wastes` |
| `attendance_records` | `attendances` |
| `gl_account_balances` | no such table; cash / AR / AP come from `gl_journal_lines` |

The month operating-expense card calls `monthAmount('expenses', 'amount')`, which defaults the date column to `invoice_date`. `expenses` has `expense_date`, so the card short-circuits to 0. The six-month chart does filter `expense_date` and is the number that moves. Changing the card would change the summary value from a permanent 0; it was not part of a measured query win.

**Schema introspection on every dashboard load.** After the fix the isolated month dashboard is still **54** queries. Most of the remainder are `pragma_table_xinfo` / `sqlite_master` from `Schema::hasColumn` (payroll, purchases, sales, expenses) plus one aggregate per widget. On MySQL each `hasTable`/`hasColumn` is an `information_schema` query until the schema is cached with `php artisan schema:cache` or the process has already resolved that table. Worth doing at deploy time; not a code change here.

**Reports load the full result, then slice in PHP.** `PaginatesReportRows` paginates an in-memory array. The stock ledger query is already `LIMIT 2000` and was 1 query, 0.16 ms, on an empty ledger. Sales and purchase analytics `get()` the grouped or filtered set so the summary can total every row. With empty production tables that is one cheap query. SQL `LIMIT/OFFSET` would drop the summary unless a second aggregate is added. Not changed, because it was not a measured cost.

**`whereDate` on production reports.** `ProductionReportService` and parts of inventory reporting wrap `production_date` / `occurred_at` in `date()`, which blocks `(status, production_date)`. `EXPLAIN` cannot show a better plan while those tables have 0 rows. Left as-is.

**HTTP wall time.** In this probe, framework and the test client dominate (about 20–60 ms) once the per-day loop is gone. The next real gain on MySQL shows up when `dana_batches`, `sales_invoices`, and `gl_journal_lines` have enough rows for the missing date indexes in section 5 to change `EXPLAIN`.

## Tests

`php artisan test` (SQLite memory): **88 passed**, 276 assertions, 19.51 s. Includes `tests/Feature/PerformanceQueryBudgetTest.php`, which fails if the month dashboard goes back to a per-day `output_kg` query (budget: under 80 queries, at most 6 `output_kg` statements), if trial balance or the balance sheet issues `gl_accounts ... limit 1`, or if the open-invoice total stops matching the per-invoice balance.
