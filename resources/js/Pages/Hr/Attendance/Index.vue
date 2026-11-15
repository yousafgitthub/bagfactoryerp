<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    attendances: Object,
    filters: Object,
    lookups: Object,
    canManage: Boolean,
});

const applyFilters = (patch) => {
    router.get(route('hr.attendance.index'), { ...props.filters, ...patch }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Attendance register"
            help-text="Filter by date range or employee. Use daily entry for bulk updates on a single day."
        >
            <template #actions>
                <Link v-if="canManage" :href="route('hr.attendance.create')" class="erp-btn-primary">+ Record</Link>
            </template>
        </ErpPageHeader>

        <div class="erp-card mb-4 flex flex-wrap gap-3 p-4">
            <input v-model="filters.from" type="date" class="erp-input" @change="applyFilters({ from: $event.target.value })" />
            <input v-model="filters.to" type="date" class="erp-input" @change="applyFilters({ to: $event.target.value })" />
            <select class="erp-input" :value="filters.employee_id || ''" @change="applyFilters({ employee_id: $event.target.value || null })">
                <option value="">All employees</option>
                <option v-for="e in lookups.employees" :key="e.id" :value="e.id">{{ e.name }}</option>
            </select>
        </div>

        <div class="erp-card p-4">
            <ErpDataTable
                :columns="[
                    { key: 'date', label: 'Date' },
                    { key: 'employee', label: 'Employee' },
                    { key: 'status', label: 'Status' },
                    { key: 'hours', label: 'Hours' },
                    { key: 'actions', label: '' },
                ]"
                empty-message="No attendance records in this range."
                empty-description="Try widening the dates or use daily entry to log today."
                :rows="
                    attendances.data.map((a) => ({
                        id: a.id,
                        date: a.attendance_date,
                        employee: a.employee?.name,
                        status: lookups.statuses[a.status] ?? a.status,
                        hours: a.working_hours,
                    }))
                "
            >
                <template #cell-actions="{ row }">
                    <Link v-if="canManage" :href="route('hr.attendance.edit', row.id)" class="erp-link text-sm">Edit</Link>
                </template>
            </ErpDataTable>
        </div>
    </ErpLayout>
</template>