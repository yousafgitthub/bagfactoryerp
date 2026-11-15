/** @typedef {{ getItem: (key: string) => string | null, setItem: (key: string, value: string) => void }} ErpStorage */

export const ERP_SIDEBAR_COLLAPSED_KEY = 'bag-factory-erp-sidebar-collapsed';

/**
 * @param {ErpStorage | null | undefined} storage
 */
export function readSidebarCollapsed(storage) {
    if (!storage) {
        return false;
    }

    return storage.getItem(ERP_SIDEBAR_COLLAPSED_KEY) === '1';
}

/**
 * @param {ErpStorage | null | undefined} storage
 * @param {boolean} collapsed
 */
export function writeSidebarCollapsed(storage, collapsed) {
    if (!storage) {
        return;
    }

    storage.setItem(ERP_SIDEBAR_COLLAPSED_KEY, collapsed ? '1' : '0');
}

/**
 * @param {ErpStorage | null | undefined} storage
 */
export function toggleSidebarCollapsed(storage) {
    const next = !readSidebarCollapsed(storage);
    writeSidebarCollapsed(storage, next);

    return next;
}