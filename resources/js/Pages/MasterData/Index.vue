<script setup>
import ErpConfirmDialog from '@/Components/Erp/ErpConfirmDialog.vue';
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    pageTitle: { type: String, required: true },
    breadcrumbs: { type: Array, default: () => [] },
    resource: { type: String, required: true },
    resourceLabel: { type: String, required: true },
    columns: { type: Array, required: true },
    rows: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    pagination: { type: Object, default: () => ({}) },
    canManage: { type: Boolean, default: false },
});

const deactivateTarget = ref(null);

const applyFilters = (patch) => {
    router.get(
        route('master-data.index', props.resource),
        { ...props.filters, ...patch },
        { preserveState: true, replace: true },
    );
};

const confirmDeactivate = () => {
    if (!deactivateTarget.value) {
        return;
    }

    router.post(
        route('master-data.deactivate', {
            resource: props.resource,
            record: deactivateTarget.value.id,
        }),
        {},
        { onFinish: () => (deactivateTarget.value = null) },
    );
};
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="resourceLabel" description="Factory master data with search, filters, and status control.">
            <template #actions>
                <Link
                    v-if="canManage"
                    :href="route('master-data.create', resource)"
                    class="erp-btn-primary"
                >
                    + New
                </Link>
            </template>
        </ErpPageHeader>

        <div class="erp-card mb-4 flex flex-wrap gap-3 p-4">
            <input
                type="search"
                class="erp-input min-w-[12rem] flex-1"
                placeholder="Search…"
                :value="filters.search"
                @change="applyFilters({ search: $event.target.value })"
            />
            <select
                class="erp-input w-40"
                :value="filters.status"
                @change="applyFilters({ status: $event.target.value })"
            >
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="under_maintenance">Under maintenance</option>
            </select>
        </div>

        <ErpDataTable
            :columns="columns"
            :rows="rows"
            :searchable="false"
            :pagination-links="pagination.links ?? []"
            :pagination-meta="pagination.meta"
            :row-actions="canManage"
        >
            <template #cell-hex_color="{ row }">
                <span
                    v-if="row.hex_color"
                    class="inline-block h-6 w-6 rounded border border-slate-200"
                    :style="{ backgroundColor: row.hex_color }"
                    :title="row.hex_color"
                />
                <span v-else class="text-erp-muted">—</span>
            </template>
            <template #cell-status="{ row }">
                <ErpStatusBadge :status="row.status" />
            </template>
            <template #row-actions="{ row }">
                <Link
                    :href="route('master-data.edit', { resource, record: row.id })"
                    class="erp-btn-secondary text-xs"
                >
                    Edit
                </Link>
                <button
                    v-if="row.status !== 'inactive'"
                    type="button"
                    class="erp-btn-secondary text-xs text-amber-700"
                    @click="deactivateTarget = row"
                >
                    Deactivate
                </button>
            </template>
        </ErpDataTable>

        <ErpConfirmDialog
            :show="!!deactivateTarget"
            title="Deactivate record?"
            :message="`Set ${deactivateTarget?.name ?? deactivateTarget?.code} to inactive. Referenced records stay in history.`"
            confirm-label="Deactivate"
            @confirm="confirmDeactivate"
            @close="deactivateTarget = null"
        />
    </ErpLayout>
</template>