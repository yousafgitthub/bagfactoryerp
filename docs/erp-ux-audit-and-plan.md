# Bag Factory ERP — UX audit and phased improvement plan

**Branch:** `cursor/erp-ux-improvement-0c65`  
**Audit date:** 2026-11-15  
**Scope:** Inventory navigation, shared patterns, priority modules (dashboard, inventory, Dana, bag, sales, purchases). No posting-rule or calculation changes.

---

## 1. Module / page inventory

| Area | ERP module key | Hub route | Primary Inertia pages |
|------|----------------|-----------|------------------------|
| Dashboard | `dashboard` | `dashboard` | `Dashboard.vue` |
| Inventory | `inventory` | `erp.inventory` | `Inventory/*` |
| Dana production | `dana-production` | `erp.dana-production` | `Dana/*` |
| Shopping bag production | `shopping-bag-production` | `erp.shopping-bag-production` | `Bag/*` |
| Machines & halls | `machines-halls` | `erp.machines-halls` | `MasterData/Hub.vue` (factory floor master data) |
| Employees | `employees` | `erp.employees` | `Hr/Employees/*`, assignments, reports |
| Attendance | `attendance` | `erp.attendance` | `Hr/Attendance/*`, overtime |
| Payroll | `payroll` | `erp.payroll` | `Hr/Payroll/*` |
| Purchases | `purchases` | `erp.purchases` | `Purchases/*` |
| Suppliers | `suppliers` | `erp.suppliers` | `Purchases/Suppliers/*` |
| Sales | `sales` | `erp.sales` | `Sales/*` |
| Customers | `customers` | `erp.customers` | `Sales/Customers/*` |
| Expenses | `expenses` | `erp.expenses` | `Expenses/*` |
| Accounting | `accounting` | `erp.accounting` | `Accounting/*` |
| Reports | `reports` | `erp.reports` | `Reports/*` |
| Website admin | `website` | `website.admin.hub` | `Website/Admin/*` |
| Settings / master data | `settings` | `erp.settings` | `MasterData/Hub.vue`, `MasterData/Index.vue`, `Form.vue` |
| Auth / profile | — | Breeze routes | `Auth/*`, `Profile/*` |
| Public website | — | `website.public.*` | `Website/*` (no ERP chrome) |

Master data CRUD is also reachable under `/master-data/{resource}` (units, warehouses, colors, etc.) from **Settings** and **Machines & Halls** hubs.

---

## 2. Issues found (pre-improvement)

### Navigation
- Flat sidebar list of 18 modules — hard to scan for operators vs finance.
- Only **Inventory** had nested links; other hubs hid workflows behind hub cards.
- **Master data**, **machines & halls**, **reports**, and **website** had no sidebar shortcuts.
- Longest-prefix active logic existed but did not consider child routes for non-inventory modules.

### Language and clarity
- Dashboard card hint for raw materials referenced “unposted ledger” (technical).
- Sales invoice stock errors exposed internal `material_id`.
- ERP success flashes used session key `success` while Inertia only shared `status` — many toasts never appeared.

### Page structure
- Priority hubs had headers but no consistent “how to use this module” pattern.
- Inner list pages (invoices, batches) lacked one-line workflow context.
- Empty states existed as components but were not used consistently on hub “recent” lists (plain text only).

### Forms and errors
- Domain exceptions (stock, payments) could surface as generic error pages instead of inline flash messages.

---

## 3. Improvement plan and priority

| Phase | Focus | Status in this pass |
|-------|--------|---------------------|
| 1 | UX audit (this document) | **Done** |
| 2 | Sidebar regrouping + collapsible children | **Done** |
| 3–4 | `ErpPageHeader` help text, `ErpModuleIntro`, flash/error | **Done** (partial modules) |
| 5 | Manufacturing workflow copy (Dana / bag) | **Done** on hubs + batch show |
| 6 | Purchase / sales step guidance | **Done** on hubs + key lists |
| 7–9 | Breadcrumbs, table toolbar consistency, more empty states | **Deferred** (backlog) |
| 8 | Contextual `helpText` on all pages | **Partial** — priority modules only |

### Backlog (next passes)

- HR (attendance daily sheet, payroll run wizard copy).
- Accounting reports and journal drill-down breadcrumbs.
- Purchases GRN / invoice show pages — mirror sales invoice intros.
- Website admin pages — align with ERP header pattern.
- Replace raw filter placeholders (“Batch #”) with labeled fields via `ErpFormField`.
- Broader `ErpEmptyState` on zero-row tables.
- Optional: sidebar search / favourites.

---

## 4. Per-module checklist (not done in this pass)

| Module | Remaining UX work |
|--------|-------------------|
| Machines & halls | Hub intro; sidebar could list common resources |
| Employees / HR | Hub intros, breadcrumb on assignment forms |
| Attendance / payroll | Daily entry field hints, payslip print affordance |
| Suppliers / customers | Directory empty states, show-page section labels |
| Expenses | Form category hints |
| Accounting | Financial statement pages — period helper text |
| Reports | Report catalog grouping labels |
| Website admin | Enquiry status legend, listing sync copy |
| Master data indexes | Hide numeric IDs in table where code exists |
| Auth | Low priority — Breeze defaults acceptable |

---

## 5. Appendix — this pass implementation log

### Pages reviewed and updated

| Page | Problems addressed |
|------|---------------------|
| `Dashboard.vue` | Clearer intro line |
| `Inventory/Hub`, Materials/Balances/Ledger indexes | `helpText`, module intro / copy |
| `Dana/Hub`, `Batches/Index`, `Batches/Show` | Workflow intro and batch guidance |
| `Bag/Hub`, `Batches/Index`, `Batches/Show` | Same |
| `Sales/Hub`, `Invoices/Index`, `Show`, `Form` | Sales flow steps, posting hints |
| `Purchases/Hub`, `Orders/Index` | Purchase flow + GRN reminder |

### Files changed (implementation)

- `resources/js/support/erpNavStructure.js` — section groups + child route map
- `resources/js/Components/Erp/ErpSidebar.vue` — grouped, collapsible nav
- `resources/js/Components/Erp/ErpModuleIntro.vue` — new
- `resources/js/Components/Erp/ErpPageHeader.vue` — `helpText`
- `resources/js/Components/Erp/ErpFlashAlert.vue` — success + error
- `app/Http/Middleware/HandleInertiaRequests.php` — flash keys
- `app/Support/ErpBusinessExceptions.php` — domain exception list
- `bootstrap/app.php` — friendly redirect on business exceptions
- `app/Services/Sales/SalesInvoiceService.php` — stock message wording only
- `app/Services/Dashboard/DashboardDataService.php` — card hint copy only
- Priority Vue pages listed above

### Tests run

- `php artisan test` (full suite)
- `npm run build`

### Remaining issues (priority modules)

- Sales/purchase **show** pages for orders, GRN, payments — no `ErpModuleIntro` yet.
- Dashboard still dense for mobile — no layout change in this pass.
- Collapsed sidebar shows parent icons only; nested modules require expand on desktop (unchanged trade-off).

---

## 6. Navigation map (sidebar)

| Section | Module | Nested links (when permitted) |
|---------|--------|-------------------------------|
| *(home)* | Dashboard | — |
| Operations | Inventory | Overview, materials, balances, ledger, movements, reconciliation |
| Operations | Dana production | Overview, batches, recipes, costing |
| Operations | Shopping bag production | Overview, products, BOM, orders, batches, costing |
| Operations | Machines & halls | Hub only (master data inside) |
| People | Employees, attendance, payroll | Hub shortcuts per `erpNavStructure.js` |
| Buy & sell | Purchases, suppliers, sales, customers | Hub shortcuts per structure file |
| Finance | Expenses, accounting, reports | Hub shortcuts |
| Administration | Website, settings | Website admin + **Factory master data** under settings |

Active item detection: longest matching path among module hub **and** declared child routes.