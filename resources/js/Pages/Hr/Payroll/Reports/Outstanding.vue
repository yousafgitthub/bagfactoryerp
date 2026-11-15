<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    records: Object,
    totalOutstanding: Number,
});
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Outstanding salary" :description="'Total outstanding: ' + totalOutstanding" />

        <div class="erp-card p-4">
            <ErpDataTable
                :columns="[
                    { key: 'period', label: 'Period' },
                    { key: 'employee', label: 'Employee' },
                    { key: 'net', label: 'Net' },
                    { key: 'paid', label: 'Paid' },
                    { key: 'outstanding', label: 'Outstanding' },
                    { key: 'actions', label: '' },
                ]"
                :rows="
                    records.data.map((r) => ({
                        id: r.id,
                        period: r.run?.period_label,
                        employee: r.employee?.name,
                        net: r.net_payable,
                        paid: r.amount_paid,
                        outstanding: r.outstanding_amount,
                    }))
                "
            >
                <template #cell-actions="{ row }">
                    <Link :href="route('hr.payroll.records.show', row.id)" class="erp-link text-sm">Open</Link>
                </template>
            </ErpDataTable>
        </div>
    </ErpLayout>
</template>