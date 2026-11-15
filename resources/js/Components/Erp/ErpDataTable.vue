<script setup>
import { computed, ref } from 'vue';
import ErpEmptyState from '@/Components/Erp/ErpEmptyState.vue';
import ErpLoadingState from '@/Components/Erp/ErpLoadingState.vue';
import ErpPagination from '@/Components/Erp/ErpPagination.vue';
import ErpTableToolbar from '@/Components/Erp/ErpTableToolbar.vue';

const props = defineProps({
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        default: () => [],
    },
    emptyMessage: {
        type: String,
        default: 'No records yet.',
    },
    emptyDescription: {
        type: String,
        default: '',
    },
    loading: {
        type: Boolean,
        default: false,
    },
    searchable: {
        type: Boolean,
        default: true,
    },
    searchKeys: {
        type: Array,
        default: null,
    },
    paginationLinks: {
        type: Array,
        default: () => [],
    },
    paginationMeta: {
        type: Object,
        default: null,
    },
    sortKey: {
        type: String,
        default: '',
    },
    sortDirection: {
        type: String,
        default: 'asc',
    },
    rowActions: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['sort']);

const search = ref('');

const filterKeys = computed(() => props.searchKeys ?? props.columns.map((column) => column.key));

const filteredRows = computed(() => {
    if (!props.searchable || !search.value.trim()) {
        return props.rows;
    }

    const needle = search.value.trim().toLowerCase();

    return props.rows.filter((row) =>
        filterKeys.value.some((key) => String(row[key] ?? '').toLowerCase().includes(needle)),
    );
});

const onSort = (column) => {
    if (!column.sortable) {
        return;
    }

    const direction =
        props.sortKey === column.key && props.sortDirection === 'asc' ? 'desc' : 'asc';

    emit('sort', { key: column.key, direction });
};
</script>

<template>
    <div class="erp-card overflow-hidden">
        <div class="overflow-hidden">
            <ErpTableToolbar
                v-if="searchable"
                v-model:search="search"
                :total="rows.length"
            >
                <template #filters>
                    <slot name="filters" />
                </template>
                <template #actions>
                    <slot name="toolbar-actions" />
                </template>
            </ErpTableToolbar>

            <ErpLoadingState v-if="loading" class="border-0 shadow-none" />

            <template v-else>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-erp-border text-sm">
                        <thead class="erp-table-head">
                            <tr>
                                <th
                                    v-for="column in columns"
                                    :key="column.key"
                                    scope="col"
                                    class="px-4 py-3"
                                >
                                    <button
                                        v-if="column.sortable"
                                        type="button"
                                        class="inline-flex items-center gap-1 font-semibold hover:text-slate-900 dark:hover:text-white"
                                        @click="onSort(column)"
                                    >
                                        {{ column.label }}
                                        <span v-if="sortKey === column.key" class="text-[10px]">
                                            {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                        </span>
                                    </button>
                                    <span v-else>{{ column.label }}</span>
                                </th>
                                <th v-if="rowActions" scope="col" class="px-4 py-3 text-right">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-erp-border bg-white dark:bg-slate-900/20">
                            <tr v-if="!filteredRows.length">
                                <td :colspan="columns.length + (rowActions ? 1 : 0)" class="p-0">
                                    <ErpEmptyState
                                        :title="emptyMessage"
                                        :description="emptyDescription"
                                        class="border-0 shadow-none"
                                    />
                                </td>
                            </tr>
                            <tr
                                v-for="(row, index) in filteredRows"
                                :key="row.id ?? index"
                                class="hover:bg-slate-50 dark:hover:bg-slate-800/40"
                            >
                                <td
                                    v-for="column in columns"
                                    :key="column.key"
                                    class="whitespace-nowrap px-4 py-3 text-slate-800 dark:text-slate-100"
                                >
                                    <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">
                                        {{ row[column.key] }}
                                    </slot>
                                </td>
                                <td v-if="rowActions" class="px-4 py-3 text-right">
                                    <slot name="row-actions" :row="row" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <ErpPagination
                    v-if="paginationLinks.length"
                    :links="paginationLinks"
                    :meta="paginationMeta"
                />
            </template>
        </div>
    </div>
</template>