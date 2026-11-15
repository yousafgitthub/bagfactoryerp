<script setup>
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';

defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    emptyTitle: {
        type: String,
        default: 'No alerts',
    },
});
</script>

<template>
    <ul v-if="items.length" class="divide-y divide-erp-border">
        <li v-for="(item, index) in items" :key="index" class="flex gap-3 py-3 first:pt-0 last:pb-0">
            <ErpStatusBadge :status="item.severity ?? 'warning'" :label="item.severity" />
            <div class="min-w-0">
                <p class="text-sm font-medium text-slate-900 dark:text-slate-50">
                    {{ item.title }}
                </p>
                <p v-if="item.detail" class="text-xs text-erp-muted">
                    {{ item.detail }}
                </p>
            </div>
        </li>
    </ul>
    <p v-else class="text-sm text-erp-muted">
        {{ emptyTitle }}
    </p>
</template>