<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, orders: Object, canManage: Boolean });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Sales orders">
            <template #actions>
                <Link v-if="canManage" :href="route('sales.orders.create')" class="erp-btn-primary">+ Order</Link>
            </template>
        </ErpPageHeader>
        <ErpDataTable
            :columns="[
                { key: 'number', label: 'Number' },
                { key: 'customer', label: 'Customer' },
                { key: 'date', label: 'Date' },
                { key: 'total', label: 'Total' },
                { key: 'status', label: 'Status' },
            ]"
            :rows="
                (orders.data || []).map((o) => ({
                    id: o.id,
                    number: o.order_number,
                    customer: o.customer?.name,
                    date: o.order_date,
                    total: o.grand_total,
                    status: o.status,
                }))
            "
        >
            <template #cell-number="{ row }">
                <Link :href="route('sales.orders.show', row.id)" class="erp-link">{{ row.number }}</Link>
            </template>
            <template #cell-status="{ row }"><ErpStatusBadge :status="row.status" /></template>
        </ErpDataTable>
    </ErpLayout>
</template>