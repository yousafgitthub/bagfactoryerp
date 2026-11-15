import assert from 'node:assert/strict';
import { describe, it } from 'node:test';
import {
    ERP_SIDEBAR_COLLAPSED_KEY,
    readSidebarCollapsed,
    toggleSidebarCollapsed,
    writeSidebarCollapsed,
} from '../../resources/js/support/erpSidebarState.js';

function memoryStorage() {
    /** @type {Record<string, string>} */
    const map = {};

    return {
        getItem: (key) => (key in map ? map[key] : null),
        setItem: (key, value) => {
            map[key] = value;
        },
    };
}

describe('erpSidebarState', () => {
    it('defaults to expanded when storage is empty', () => {
        assert.equal(readSidebarCollapsed(memoryStorage()), false);
    });

    it('persists collapsed flag', () => {
        const storage = memoryStorage();
        writeSidebarCollapsed(storage, true);
        assert.equal(readSidebarCollapsed(storage), true);
        writeSidebarCollapsed(storage, false);
        assert.equal(readSidebarCollapsed(storage), false);
    });

    it('toggles collapsed state', () => {
        const storage = memoryStorage();
        assert.equal(toggleSidebarCollapsed(storage), true);
        assert.equal(storage.getItem(ERP_SIDEBAR_COLLAPSED_KEY), '1');
        assert.equal(toggleSidebarCollapsed(storage), false);
    });
});