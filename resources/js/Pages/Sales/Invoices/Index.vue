<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, invoices: Object, canManage: Boolean });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Sales invoices">
            <template #actions>
                <Link v-if="canManage" :href="route('sales.invoices.create')" class="erp-btn-primary">+ Invoice</Link>
            </template>
        </ErpPageHeader>
        <ErpDataTable
            :columns="[
                { key: 'number', label: 'Invoice' },
                { key: 'customer', label: 'Customer' },
                { key: 'date', label: 'Date' },
                { key: 'total', label: 'Total' },
                { key: 'status', label: 'Status' },
            ]"
            :rows="
                (invoices.data || []).map((i) => ({
                    id: i.id,
                    number: i.invoice_number,
                    customer: i.customer?.name,
                    date: i.invoice_date,
                    total: i.grand_total,
                    status: i.status,
                }))
            "
        >
            <template #cell-number="{ row }">
                <Link :href="route('sales.invoices.show', row.id)" class="erp-link">{{ row.number }}</Link>
            </template>
        </ErpDataTable>
    </ErpLayout>
</template>