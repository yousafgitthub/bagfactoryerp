<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    employees: Object,
    filters: Object,
    lookups: Object,
    canManage: Boolean,
});

const applyFilters = (patch) => {
    router.get(route('hr.employees.index'), { ...props.filters, ...patch }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Employee directory" description="Search by code, name, or phone; filter by department.">
            <template #actions>
                <Link v-if="canManage" :href="route('hr.employees.create')" class="erp-btn-primary">+ New</Link>
            </template>
        </ErpPageHeader>

        <div class="erp-card mb-4 flex flex-wrap gap-3 p-4">
            <input
                type="search"
                class="erp-input max-w-xs"
                placeholder="Search"
                :value="filters.search"
                @input="applyFilters({ search: $event.target.value })"
            />
            <select class="erp-input max-w-xs" :value="filters.department_id || ''" @change="applyFilters({ department_id: $event.target.value || null })">
                <option value="">All departments</option>
                <option v-for="d in lookups.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
            </select>
            <select class="erp-input max-w-xs" :value="filters.status || ''" @change="applyFilters({ status: $event.target.value })">
                <option value="">All statuses</option>
                <option v-for="(label, key) in lookups.statuses" :key="key" :value="key">{{ label }}</option>
            </select>
        </div>

        <div class="erp-card p-4">
            <ErpDataTable
                :columns="[
                    { key: 'code', label: 'Code' },
                    { key: 'name', label: 'Name' },
                    { key: 'department', label: 'Department' },
                    { key: 'designation', label: 'Designation' },
                    { key: 'salary', label: 'Salary' },
                    { key: 'status', label: 'Status' },
                    { key: 'actions', label: '' },
                ]"
                :rows="
                    employees.data.map((e) => ({
                        id: e.id,
                        code: e.employee_code,
                        name: e.name,
                        department: e.department?.name ?? '—',
                        designation: e.designation?.name ?? '—',
                        salary: e.salary_type === 'monthly' ? e.basic_monthly_salary : e.daily_wage_rate,
                        status: e.status,
                    }))
                "
                empty-message="No employees yet."
            >
                <template #cell-status="{ row }">
                    <ErpStatusBadge :status="row.status" />
                </template>
                <template #cell-actions="{ row }">
                    <Link :href="route('hr.employees.show', row.id)" class="erp-link text-sm">Profile</Link>
                </template>
            </ErpDataTable>
        </div>
    </ErpLayout>
</template>