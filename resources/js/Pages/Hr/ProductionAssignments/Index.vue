<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    assignments: Object,
    filters: Object,
    canManage: Boolean,
    assignmentTypes: Object,
    lookups: Object,
});

const apply = (patch) => {
    router.get(route('hr.production-assignments.index'), { ...props.filters, ...patch }, { preserveState: true });
};
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Production assignments">
            <template #actions>
                <Link v-if="canManage" :href="route('hr.production-assignments.create')" class="erp-btn-primary">+ Assignment</Link>
            </template>
        </ErpPageHeader>

        <div class="erp-card mb-4 flex gap-3 p-4">
            <select class="erp-input" :value="filters.employee_id || ''" @change="apply({ employee_id: $event.target.value || null })">
                <option value="">All employees</option>
                <option v-for="e in lookups.employees" :key="e.id" :value="e.id">{{ e.name }}</option>
            </select>
            <select class="erp-input" :value="filters.assignment_type || ''" @change="apply({ assignment_type: $event.target.value })">
                <option value="">All types</option>
                <option v-for="(label, key) in assignmentTypes" :key="key" :value="key">{{ label }}</option>
            </select>
        </div>

        <div class="erp-card p-4">
            <ErpDataTable
                :columns="[
                    { key: 'employee', label: 'Employee' },
                    { key: 'type', label: 'Type' },
                    { key: 'target', label: 'Target' },
                    { key: 'period', label: 'Period' },
                    { key: 'actions', label: '' },
                ]"
                :rows="
                    assignments.data.map((a) => ({
                        id: a.id,
                        employee: a.employee?.name,
                        type: assignmentTypes[a.assignment_type] ?? a.assignment_type,
                        target: a.machine?.name ?? a.department?.name ?? a.dana_batch?.batch_number ?? a.bag_production_batch?.batch_number ?? a.bag_production_order?.order_number ?? '—',
                        period: [a.assigned_from, a.assigned_to].filter(Boolean).join(' → ') || '—',
                    }))
                "
            >
                <template #cell-actions="{ row }">
                    <Link v-if="canManage" :href="route('hr.production-assignments.edit', row.id)" class="erp-link text-sm">Edit</Link>
                </template>
            </ErpDataTable>
        </div>
    </ErpLayout>
</template>