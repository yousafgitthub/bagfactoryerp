# MySQL migration reconcile — `bag_factory_erp`

Reconciliation notes for `SQLSTATE[42S01]: 1050 Table 'employees' already exists` when running `php artisan migrate` against MySQL database `bag_factory_erp`.

**Verified:** 2026-11-15 (local Laragon, PHP 8.5.4, MySQL 8.4).

## Summary

| Item | Value |
|------|--------|
| Failing step | First pending migration, on `Schema::create('employees', …)` |
| Migration file | `database/migrations/2026_11_15_170000_create_hr_employee_tables.php` |
| Migration name (for `migrations` table) | `2026_11_15_170000_create_hr_employee_tables` |
| Last recorded batch | `2` |
| Recommended action | Mark HR migration as ran (schema matches), then `php artisan migrate` |

## Current `migrate:status`

Ran through batch **2**:

- `0001_01_01_000000_create_users_table` … batch 1
- `0001_01_01_000001_create_cache_table` … batch 1
- `0001_01_01_000002_create_jobs_table` … batch 1
- `2026_11_15_062952_create_permission_tables` … batch 1
- `2026_11_15_100000_create_factory_master_data_tables` … batch 2
- `2026_11_15_120000_create_inventory_tables` … batch 2
- `2026_11_15_140000_create_dana_production_tables` … batch 2
- `2026_11_15_160000_create_bag_production_tables` … batch 2

**Pending:**

| Migration | Creates (first table) |
|-----------|------------------------|
| `2026_11_15_170000_create_hr_employee_tables` | `employees` (+ 4 HR tables) |
| `2026_11_15_180000_create_payroll_tables` | `payroll_runs` |
| `2026_11_15_190000_create_purchase_tables` | `suppliers` |
| `2026_11_15_200000_create_sales_tables` | `customers` |
| `2026_11_15_210000_create_accounting_tables` | `gl_accounts` |

## Schema vs migration (HR batch)

Migration `2026_11_15_170000_create_hr_employee_tables` defines five tables. **All five already exist** in MySQL; **none** of the later pending modules’ core tables exist yet (`payroll_runs`, `suppliers`, `customers`, `gl_accounts` are absent).

| Table | In DB | Data (sample) | Match migration |
|-------|-------|---------------|-----------------|
| `employees` | Yes | 0 rows | Yes — columns, unique `employee_code`, composite index `(department_id, status)`, FKs to `departments` / `designations` |
| `employee_salary_rates` | Yes | — | Present (not fully diffed; created in same migration file) |
| `attendances` | Yes | — | Present |
| `overtime_records` | Yes | — | Present |
| `employee_production_assignments` | Yes | — | Present |

`employees` column layout matches Laravel’s expected DDL from the migration (including `decimal(14,2)`, `varchar(32)` codes, `status` default `active`, `is_active` default `1`).

**Conclusion:** Only the **HR employee** migration is out of sync (tables exist, `migrations` row missing). Payroll / purchase / sales / accounting are genuinely pending and safe to run after HR is reconciled.

## Why `migrations` is out of sync

Most plausible explanation on MySQL:

1. **`CREATE TABLE` auto-commits** — each statement in `2026_11_15_170000_create_hr_employee_tables` commits immediately.
2. The migration’s `up()` method **finished creating all five tables**, but the process **did not persist** the row in `migrations` (e.g. `artisan migrate` interrupted after DDL, connection drop, or killed terminal).
3. A later `php artisan migrate` retries from the pending HR migration and fails immediately on `employees` because it already exists.

Less likely here (empty HR tables, full five-table set): manual SQL import or hand-run migration SQL. Batch **2** migrations are recorded normally, so this is not a full “empty migrations table” restore.

## Safest fix (recommended)

**Do not** use `migrate:fresh`, `db:wipe`, `DROP TABLE`, or `TRUNCATE`.

### Step 1 — Re-verify (optional but good practice)

From project root:

```powershell
& "D:\laragon\bin\php\php-8.5.4-Win32-vs17-x64\php.exe" artisan migrate:status
```

Confirm `2026_11_15_170000_create_hr_employee_tables` is still **Pending** and that HR tables exist in MySQL:

```sql
SHOW TABLES LIKE 'employee%';
SHOW TABLES LIKE 'attendances';
SHOW TABLES LIKE 'overtime%';
```

### Step 2 — Baseline the HR migration

If Step 1 matches this document, record the migration as already applied. Use batch **3** (next after current max batch **2**):

```sql
INSERT INTO migrations (migration, batch)
VALUES ('2026_11_15_170000_create_hr_employee_tables', 3);
```

> **Note:** This only updates Laravel’s migration history. It does not change table data. If your `employees` schema **does not** match the migration, stop and use the alternative below instead of inserting.

### Step 3 — Run remaining migrations

```powershell
cd D:\laragon\www\bagfactoryerp
& "D:\laragon\bin\php\php-8.5.4-Win32-vs17-x64\php.exe" artisan migrate
```

Expected: migrations `2026_11_15_180000` through `2026_11_15_210000` run in batch **4** (Laravel increments batch per `migrate` invocation).

Dry-run preview after Step 2:

```powershell
& "D:\laragon\bin\php\php-8.5.4-Win32-vs17-x64\php.exe" artisan migrate --pretend
```

### Step 4 — Confirm

```powershell
& "D:\laragon\bin\php\php-8.5.4-Win32-vs17-x64\php.exe" artisan migrate:status
```

All migrations should show **Ran**.

## If HR schema does *not* match

1. Export current DDL (`SHOW CREATE TABLE employees`, etc.) and compare to `2026_11_15_170000_create_hr_employee_tables.php`.
2. Add a **new** additive migration (e.g. `2026_11_15_220000_reconcile_hr_employee_tables.php`) that uses `Schema::table` / conditional `hasColumn` / `hasTable` to align differences — never drop production tables.
3. Only after schema matches, insert the **original** HR migration name as in Step 2, or run a one-time idempotent rewrite of `170000` in a dev branch (not recommended on live DB without review).

## Quick reference

| Command | Purpose |
|---------|---------|
| `php artisan migrate:status` | See pending vs ran |
| `php artisan migrate --pretend` | SQL preview without executing |
| `php artisan migrate` | Apply pending migrations after reconcile |

| MySQL | Purpose |
|-------|---------|
| `SELECT migration, batch FROM migrations ORDER BY id;` | Audit history |
| `INSERT INTO migrations (migration, batch) VALUES ('2026_11_15_170000_create_hr_employee_tables', 3);` | Baseline HR when schema matches |