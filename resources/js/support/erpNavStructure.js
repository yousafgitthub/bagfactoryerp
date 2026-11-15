/**
 * Sidebar grouping and nested links (client-side only; permissions still from ErpNavigation).
 */

export const ERP_NAV_SECTIONS = [
    { key: 'home', label: null },
    { key: 'operations', label: 'Operations' },
    { key: 'people', label: 'People' },
    { key: 'trading', label: 'Buy & sell' },
    { key: 'finance', label: 'Finance' },
    { key: 'admin', label: 'Administration' },
];

export const ERP_MODULE_SECTION = {
    dashboard: 'home',
    inventory: 'operations',
    'dana-production': 'operations',
    'shopping-bag-production': 'operations',
    'machines-halls': 'operations',
    employees: 'people',
    attendance: 'people',
    payroll: 'people',
    purchases: 'trading',
    suppliers: 'trading',
    sales: 'trading',
    customers: 'trading',
    expenses: 'finance',
    accounting: 'finance',
    reports: 'finance',
    website: 'admin',
    settings: 'admin',
};

/** @type {Record<string, { label: string, routeName: string }[]>} */
export const ERP_NAV_CHILDREN = {
    inventory: [
        { label: 'Overview', routeName: 'erp.inventory' },
        { label: 'Materials', routeName: 'inventory.materials.index' },
        { label: 'Stock balances', routeName: 'inventory.balances.index' },
        { label: 'Stock ledger', routeName: 'inventory.ledger.index' },
        { label: 'Movements', routeName: 'inventory.transactions.index' },
        { label: 'Reconciliation', routeName: 'inventory.reconciliation' },
    ],
    'dana-production': [
        { label: 'Overview', routeName: 'erp.dana-production' },
        { label: 'Production batches', routeName: 'dana.batches.index' },
        { label: 'Recipes', routeName: 'dana.recipes.index' },
        { label: 'Costing report', routeName: 'dana.reports.costing' },
    ],
    'shopping-bag-production': [
        { label: 'Overview', routeName: 'erp.shopping-bag-production' },
        { label: 'Products', routeName: 'bag.products.index' },
        { label: 'Bill of materials', routeName: 'bag.bom.index' },
        { label: 'Production orders', routeName: 'bag.orders.index' },
        { label: 'Production batches', routeName: 'bag.batches.index' },
        { label: 'Costing report', routeName: 'bag.reports.costing' },
    ],
    purchases: [
        { label: 'Overview', routeName: 'erp.purchases' },
        { label: 'Purchase orders', routeName: 'purchases.orders.index' },
        { label: 'Goods receipts (GRN)', routeName: 'purchases.goods-receipts.index' },
        { label: 'Purchase invoices', routeName: 'purchases.invoices.index' },
        { label: 'Supplier payments', routeName: 'purchases.payments.index' },
        { label: 'Purchase returns', routeName: 'purchases.returns.index' },
    ],
    sales: [
        { label: 'Overview', routeName: 'erp.sales' },
        { label: 'Sales orders', routeName: 'sales.orders.index' },
        { label: 'Sales invoices', routeName: 'sales.invoices.index' },
        { label: 'Customer receipts', routeName: 'sales.receipts.index' },
        { label: 'Sales returns', routeName: 'sales.returns.index' },
    ],
    employees: [
        { label: 'Overview', routeName: 'erp.employees' },
        { label: 'Employee directory', routeName: 'hr.employees.index' },
        { label: 'Production assignments', routeName: 'hr.production-assignments.index' },
    ],
    attendance: [
        { label: 'Overview', routeName: 'erp.attendance' },
        { label: 'Attendance register', routeName: 'hr.attendance.index' },
        { label: 'Daily sheet', routeName: 'hr.attendance.daily' },
        { label: 'Overtime', routeName: 'hr.overtime.index' },
    ],
    payroll: [
        { label: 'Overview', routeName: 'erp.payroll' },
        { label: 'Payroll runs', routeName: 'hr.payroll.runs.index' },
        { label: 'Staff advances', routeName: 'hr.payroll.advances.index' },
        { label: 'Staff loans', routeName: 'hr.payroll.loans.index' },
    ],
    accounting: [
        { label: 'Overview', routeName: 'erp.accounting' },
        { label: 'Chart of accounts', routeName: 'accounting.gl-accounts.index' },
        { label: 'Journal entries', routeName: 'accounting.journals.index' },
        { label: 'Treasury accounts', routeName: 'accounting.treasury.index' },
    ],
    expenses: [
        { label: 'Overview', routeName: 'erp.expenses' },
        { label: 'Expense entries', routeName: 'expenses.entries.index' },
    ],
    reports: [
        { label: 'Overview', routeName: 'erp.reports' },
    ],
    website: [
        { label: 'Overview', routeName: 'website.admin.hub' },
        { label: 'Enquiries', routeName: 'website.admin.enquiries.index' },
        { label: 'Public listings', routeName: 'website.admin.listings.index' },
        { label: 'Site settings', routeName: 'website.admin.settings.edit' },
    ],
    settings: [
        { label: 'Factory master data', routeName: 'erp.settings' },
    ],
    customers: [
        { label: 'Overview', routeName: 'erp.customers' },
        { label: 'Customer directory', routeName: 'sales.customers.index' },
    ],
    suppliers: [
        { label: 'Overview', routeName: 'erp.suppliers' },
        { label: 'Supplier directory', routeName: 'purchases.suppliers.index' },
    ],
};

export const ERP_NAV_ICONS = {
    dashboard: 'home',
    inventory: 'box',
    'dana-production': 'gear',
    'shopping-bag-production': 'bag',
    'machines-halls': 'factory',
    employees: 'users',
    attendance: 'clock',
    payroll: 'wallet',
    purchases: 'cart',
    suppliers: 'truck',
    sales: 'trend',
    customers: 'users',
    expenses: 'receipt',
    accounting: 'building',
    reports: 'chart',
    website: 'globe',
    settings: 'settings',
};

/**
 * @param {string} moduleKey
 * @returns {{ label: string, routeName: string, href: string }[]}
 */
export function navChildrenFor(moduleKey) {
    const defs = ERP_NAV_CHILDREN[moduleKey] ?? [];

    return defs
        .filter((link) => link.routeName && route().has(link.routeName))
        .map((link) => ({
            ...link,
            href: route(link.routeName),
        }));
}