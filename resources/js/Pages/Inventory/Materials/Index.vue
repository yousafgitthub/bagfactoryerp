<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    materials: Object,
    filters: Object,
    canManage: Boolean,
});

const applyFilters = (patch) => {
    router.get(route('inventory.materials.index'), { ...props.filters, ...patch }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Materials" help-text="Each material has a code, unit, bucket (raw, Dana, or finished bag), and optional reorder alert.">
            <template #actions>
                <Link v-if="canManage" :href="route('inventory.materials.create')" class="erp-btn-primary">+ New</Link>
            </template>
        </ErpPageHeader>

        <div class="erp-card p-4">
            <input
                type="search"
                class="erp-input mb-4 max-w-xs"
                placeholder="Search code or name"
                :value="filters.search"
                @input="applyFilters({ search: $event.target.value })"
            />

            <ErpDataTable
                :columns="[
                    { key: 'code', label: 'Code' },
                    { key: 'name', label: 'Name' },
                    { key: 'category', label: 'Category' },
                    { key: 'unit', label: 'Unit' },
                    { key: 'reorder', label: 'Reorder' },
                    { key: 'status', label: 'Status' },
                    { key: 'actions', label: '' },
                ]"
                :rows="
                    materials.data.map((m) => ({
                        id: m.id,
                        code: m.code,
                        name: m.name,
                        category: m.category?.name ?? '—',
                        unit: m.unit?.symbol ?? m.unit?.code,
                        reorder: m.reorder_level,
                        status: m.status,
                    }))
                "
                empty-message="No materials yet."
            >
                <template #cell-status="{ row }">
                    <ErpStatusBadge :status="row.status" />
                </template>
                <template #cell-actions="{ row }">
                    <Link :href="route('inventory.materials.show', row.id)" class="erp-link text-sm">View</Link>
                </template>
            </ErpDataTable>
        </div>
    </ErpLayout>
</template>