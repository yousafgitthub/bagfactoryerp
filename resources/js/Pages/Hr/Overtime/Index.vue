<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import InputError from '@/Components/InputError.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    records: Object,
    filters: Object,
    lookups: Object,
    canManage: Boolean,
    canApprove: Boolean,
});

const applyFilters = (patch) => {
    router.get(route('hr.overtime.index'), { ...props.filters, ...patch }, { preserveState: true, replace: true });
};

const createForm = useForm({
    employee_id: '',
    overtime_date: new Date().toISOString().slice(0, 10),
    hours: 1,
    rate_per_hour: '',
    notes: '',
});

const submitOvertime = () => {
    createForm.post(route('hr.overtime.store'), { preserveScroll: true, onSuccess: () => createForm.reset('hours', 'notes') });
};

const approve = (id, decision) => {
    router.post(route('hr.overtime.approve', id), { decision }, { preserveScroll: true });
};
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Overtime register" />

        <form v-if="canManage" class="erp-card mb-6 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5" @submit.prevent="submitOvertime">
            <select v-model="createForm.employee_id" class="erp-input" required>
                <option value="">Employee</option>
                <option v-for="e in lookups.employees" :key="e.id" :value="e.id">{{ e.name }}</option>
            </select>
            <input v-model="createForm.overtime_date" type="date" class="erp-input" required />
            <input v-model="createForm.hours" type="number" step="0.01" class="erp-input" placeholder="Hours" required />
            <input v-model="createForm.rate_per_hour" type="number" step="0.0001" class="erp-input" placeholder="Rate (optional)" />
            <button type="submit" class="erp-btn-primary" :disabled="createForm.processing">Add</button>
            <InputError :message="createForm.errors.hours" class="sm:col-span-2" />
        </form>

        <div class="erp-card mb-4 flex flex-wrap gap-3 p-4">
            <input type="date" class="erp-input" :value="filters.from" @change="applyFilters({ from: $event.target.value })" />
            <input type="date" class="erp-input" :value="filters.to" @change="applyFilters({ to: $event.target.value })" />
            <select class="erp-input" :value="filters.approvalStatus || ''" @change="applyFilters({ approval_status: $event.target.value })">
                <option value="">All statuses</option>
                <option v-for="(label, key) in lookups.approvalStatuses" :key="key" :value="key">{{ label }}</option>
            </select>
        </div>

        <div class="erp-card p-4">
            <ErpDataTable
                :columns="[
                    { key: 'date', label: 'Date' },
                    { key: 'employee', label: 'Employee' },
                    { key: 'hours', label: 'Hours' },
                    { key: 'amount', label: 'Amount' },
                    { key: 'status', label: 'Status' },
                    { key: 'actions', label: '' },
                ]"
                :rows="
                    records.data.map((r) => ({
                        id: r.id,
                        date: r.overtime_date,
                        employee: r.employee?.name,
                        hours: r.hours,
                        amount: r.calculated_amount,
                        status: r.approval_status,
                    }))
                "
            >
                <template #cell-status="{ row }">
                    <ErpStatusBadge :status="row.status" />
                </template>
                <template #cell-actions="{ row }">
                    <template v-if="canApprove && row.status === 'pending'">
                        <button type="button" class="erp-link text-sm" @click="approve(row.id, 'approved')">Approve</button>
                        <button type="button" class="erp-link ml-2 text-sm text-red-600" @click="approve(row.id, 'rejected')">Reject</button>
                    </template>
                </template>
            </ErpDataTable>
        </div>
    </ErpLayout>
</template>