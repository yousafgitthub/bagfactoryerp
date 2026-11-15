# DEMO sample data

Idempotent sample rows for the dashboard and the public catalog. Every sample party, material, and document is marked `DEMO-`. Posted stock, production, purchases, sales, payroll, and expenses go through the existing services. The seeder does not delete or rewrite rows that are not marked `DEMO-`.

## Command

From `D:\laragon\www\bagfactoryerp`:

```
D:\laragon\bin\php\php-8.5.4-Win32-vs17-x64\php.exe artisan db:seed --class=DemoSampleDataSeeder --no-interaction
```

`DemoSampleDataSeeder` is not called from `DatabaseSeeder`, so `php artisan test` (SQLite memory) does not load these rows.

The seeder was run again on 15 Nov 2026 after the first successful post. The printed counts were unchanged:

| Module | DEMO rows |
| --- | ---: |
| Customers | 2 |
| Suppliers | 2 |
| Employees | 2 |
| Materials | 14 |
| Public bag products | 3 |
| Public dana material | 1 |
| Dana batches | 2 |
| Bag production batches | 2 |
| Purchase orders (and matching invoices / receipts) | 2 |
| Sales orders | 1 |
| Sales invoices | 2 |
| Operating expenses | 5 |
| Payroll runs | 1 |
| Attendance rows | 22 |

## Dashboard spot-check (15 Nov 2026)

`DashboardDataService` against MySQL `bag_factory_erp`, compared with the posted DEMO documents:

| Card | Value | Seeded source |
| --- | ---: | --- |
| Today's bag production | 1,500 pcs | 1,000 plain + 500 printed |
| Raw material stock | 5,529.5 | Raw-bucket balances. The card sums kg and pieces together (5,000 handles + 200 packing film + remaining kg). |
| Finished bag stock | 1,340 pcs | 1,500 produced − 160 invoiced |
| Month sales | 4,645 | Both posted invoices |
| Dana stock | 117.68 kg | 132 kg output − 14.32 kg issued to bag batches |
| Today's dana production | 132 kg | 94 + 38 |
| Today's wastage | 8.5 | Dana waste quantity 8 + bag waste 0.5 kg |
| Waste percentage | 6.06% | Dana waste 8 / dana output 132. Bag waste is not in this percentage. |
| Active employees / today's attendance | 2 / 2 | Both DEMO employees present today |
| Month payroll | 58,900 | Approved November 2026 run, partially paid |
| Month purchases | 66,250 | Both posted supplier invoices |
| Month operating expenses | 75,000 | Electricity 18,500 + rent 42,000 + transport 6,500 + maintenance 4,800 + utilities 3,200 |
| Cash on hand | 136,022.5 | Posted GL on the cash account |
| Receivables | 3,122.5 | Invoice totals minus receipts |
| Payables | 35,750 | Supplier invoice totals minus payments |

Machine-wise bag pieces stay 0. That card only sums dana output.

## Public site

Guest routes:

- `/` home
- `/about`
- `/products` and `/products/{slug}` (search `?q=`, category `/products/category/{slug}`)
- `/custom-orders`
- `/request-a-quote`
- `/manufacturing`
- `/quality-control` (demonstration steps only)
- `/gallery`
- `/contact`

Published catalog items: DEMO plain T-shirt bag, DEMO printed loop-handle bag, DEMO custom die-cut bag, and DEMO natural dana. Images are generated PNGs under `storage/app/private/website/listings/`. Product copy is labeled sample catalog text. Prices and stock are not shown. There is no minimum-order, printing, or customization column, so those are not shown as specs. The quote form is where a buyer asks for size, color, or print changes.

Staff review enquiries at `/website/enquiries` (`website.admin.enquiries.index`), permission `website.view`. Artwork uploads stay jpg, png, webp, or pdf, 5 MB maximum. SVG is rejected.

Website contact fields (phone, email, address, WhatsApp) are left blank. No testimonials were published.

## Tests

- `php artisan test`: 96 passed (466 assertions).
- `npm run build`: succeeded (`built in 7.17s`).
- `PublicWebsiteTest` covers a guest seeing a DEMO public product and a quote creating a `website_enquiries` row.
- `PerformanceQueryBudgetTest` and `DashboardDataTest` passed after the dashboard lookups were pointed at `bag_production_batches`, `attendances`, `expenses.expense_date`, and posted `gl_journal_lines`.

## Limitations

- There is no company-settings table. Payment methods are the existing cash / bank / cheque config, not a table. Bag type, size, and thickness are columns on `bag_products`. Colors are rows in `colors`.
- Machine capacity is left null. Phone, email, and address on DEMO parties are null.
- Handles and packing film are opening stock only. Bag consumption is in kilograms, so piece-counted handles are not issued on a batch.
- The custom die-cut bag is catalog-only. It has no production batch.
- Production electricity on a batch is batch costing. Bag and dana accounting integration is off, so those amounts are not a second GL expense. Salaries are posted only by payroll.
- Payroll is skipped when any active employee code is outside `DEMO-`, or when a non-DEMO payroll run already owns the month, because generation includes every active employee.
- The raw-stock card mixes kilograms and pieces because that is the existing bucket sum.
- Opening DEMO cash and the other postings share the live treasury and general ledger. This database had no prior business documents when the seeder first ran.
