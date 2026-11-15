<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    runs: Object,
    statuses: Object,
    canManage: Boolean,
});
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Payroll runs">
            <template #actions>
                <Link v-if="canManage" :href="route('hr.payroll.runs.create')" class="erp-btn-primary">+ New run</Link>
            </template>
        </ErpPageHeader>

        <div class="erp-card p-4">
            <ErpDataTable
                :columns="[
                    { key: 'period', label: 'Period' },
                    { key: 'status', label: 'Status' },
                    { key: 'records', label: 'Employees' },
                    { key: 'net', label: 'Net payable' },
                    { key: 'paid', label: 'Paid' },
                    { key: 'actions', label: '' },
                ]"
                :rows="
                    runs.data.map((r) => ({
                        id: r.id,
                        period: r.period_label,
                        status: r.status,
                        records: r.records_count,
                        net: r.total_net,
                        paid: r.total_paid,
                    }))
                "
            >
                <template #cell-status="{ row }">
                    <ErpStatusBadge :status="row.status" />
                </template>
                <template #cell-actions="{ row }">
                    <Link :href="route('hr.payroll.runs.show', row.id)" class="erp-link text-sm">Open</Link>
                </template>
            </ErpDataTable>
        </div>
    </ErpLayout>
</template>