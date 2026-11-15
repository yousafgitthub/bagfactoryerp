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
        <ErpPageHeader title="Purchase orders">
            <template #actions>
                <Link v-if="canManage" :href="route('purchases.orders.create')" class="erp-btn-primary">+ PO</Link>
            </template>
        </ErpPageHeader>
        <ErpDataTable
            :columns="[
                { key: 'number', label: 'PO #' },
                { key: 'supplier', label: 'Supplier' },
                { key: 'date', label: 'Date' },
                { key: 'total', label: 'Total' },
                { key: 'status', label: 'Status' },
                { key: 'actions', label: '' },
            ]"
            :rows="
                (orders.data || []).map((o) => ({
                    id: o.id,
                    number: o.order_number,
                    supplier: o.supplier?.name,
                    date: o.order_date,
                    total: o.grand_total,
                    status: o.status,
                }))
            "
        >
            <template #cell-status="{ row }"><ErpStatusBadge :status="row.status" /></template>
            <template #cell-actions="{ row }">
                <Link :href="route('purchases.orders.show', row.id)" class="erp-link text-sm">Open</Link>
            </template>
        </ErpDataTable>
    </ErpLayout>
</template>