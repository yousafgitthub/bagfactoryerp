<script setup>
const model = defineModel('search', {
    type: String,
    default: '',
});

defineProps({
    searchPlaceholder: {
        type: String,
        default: 'Search records…',
    },
    total: {
        type: Number,
        default: null,
    },
});
</script>

<template>
    <div class="flex flex-col gap-3 border-b border-erp-border px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
            <label class="sr-only" for="erp-table-search">Search</label>
            <input
                id="erp-table-search"
                v-model="model"
                type="search"
                class="erp-input max-w-md px-3 py-2"
                :placeholder="searchPlaceholder"
            />
            <slot name="filters" />
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
            <p v-if="total !== null" class="text-xs text-erp-muted tabular-nums">
                {{ total }} records
            </p>
        </div>
    </div>
</template>