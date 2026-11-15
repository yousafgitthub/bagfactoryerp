<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, invoices: Object, canManage: Boolean });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Purchase invoices"
            help-text="Supplier bills after goods are received. Post each invoice before recording payments."
        >
            <template #actions>
                <Link v-if="canManage" :href="route('purchases.invoices.create')" class="erp-btn-primary">+ Invoice</Link>
            </template>
        </ErpPageHeader>
        <ErpDataTable
            :columns="[
                { key: 'number', label: 'Invoice' },
                { key: 'supplier', label: 'Supplier' },
                { key: 'date', label: 'Date' },
                { key: 'total', label: 'Total' },
                { key: 'paid', label: 'Paid' },
                { key: 'status', label: 'Status' },
                { key: 'actions', label: '' },
            ]"
            empty-message="No purchase invoices yet."
            empty-description="Create an invoice when the supplier bill arrives."
            :rows="
                (invoices.data || []).map((inv) => ({
                    id: inv.id,
                    number: inv.invoice_number,
                    supplier: inv.supplier?.name,
                    date: inv.invoice_date,
                    total: inv.grand_total,
                    paid: inv.amount_paid,
                    status: inv.status,
                }))
            "
        >
            <template #cell-status="{ row }"><ErpStatusBadge :status="row.status" /></template>
            <template #cell-actions="{ row }">
                <Link :href="route('purchases.invoices.show', row.id)" class="erp-link text-sm">Open</Link>
            </template>
        </ErpDataTable>
    </ErpLayout>
</template>