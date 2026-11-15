import { onMounted, ref, watch } from 'vue';
import {
    ERP_SIDEBAR_COLLAPSED_KEY,
    readSidebarCollapsed,
    writeSidebarCollapsed,
} from '@/support/erpSidebarState';

export { ERP_SIDEBAR_COLLAPSED_KEY };

export function useErpSidebarCollapsed() {
    const collapsed = ref(false);

    onMounted(() => {
        collapsed.value = readSidebarCollapsed(localStorage);
    });

    watch(collapsed, (value) => {
        writeSidebarCollapsed(localStorage, value);
    });

    const toggle = () => {
        collapsed.value = !collapsed.value;
    };

    return { collapsed, toggle };
}