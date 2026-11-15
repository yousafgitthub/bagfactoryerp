<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    advances: Object,
    filters: Object,
    lookups: Object,
    canManage: Boolean,
});

const apply = (patch) =>
    router.get(route('hr.payroll.advances.index'), { ...props.filters, ...patch }, { preserveState: true, replace: true });
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Salary advances">
            <template #actions>
                <Link v-if="canManage" :href="route('hr.payroll.advances.create')" class="erp-btn-primary">+ Advance</Link>
            </template>
        </ErpPageHeader>

        <div class="erp-card mb-4 flex flex-wrap gap-3 p-4">
            <select class="erp-input" :value="filters.employee_id || ''" @change="apply({ employee_id: $event.target.value || null })">
                <option value="">All employees</option>
                <option v-for="e in lookups.employees" :key="e.id" :value="e.id">{{ e.name }}</option>
            </select>
        </div>

        <div class="erp-card p-4">
            <ErpDataTable
                :columns="[
                    { key: 'date', label: 'Date' },
                    { key: 'employee', label: 'Employee' },
                    { key: 'amount', label: 'Amount' },
                    { key: 'outstanding', label: 'Outstanding' },
                    { key: 'status', label: 'Status' },
                ]"
                :rows="
                    advances.data.map((a) => ({
                        id: a.id,
                        date: a.advance_date,
                        employee: a.employee?.name,
                        amount: a.amount,
                        outstanding: a.outstanding_balance,
                        status: a.status,
                    }))
                "
            >
                <template #cell-status="{ row }">
                    <ErpStatusBadge :status="row.status" />
                </template>
            </ErpDataTable>
        </div>
    </ErpLayout>
</template>