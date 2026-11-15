<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, receipts: Object });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Goods receipts"
            help-text="GRNs increase stock when posted. Raise one when material arrives against a purchase order."
        />
        <ErpDataTable
            :columns="[
                { key: 'number', label: 'GRN' },
                { key: 'po', label: 'PO' },
                { key: 'supplier', label: 'Supplier' },
                { key: 'date', label: 'Date' },
                { key: 'status', label: 'Status' },
                { key: 'actions', label: '' },
            ]"
            empty-message="No goods receipts yet."
            empty-description="Post a GRN when supplier material reaches the warehouse."
            :rows="
                (receipts.data || []).map((r) => ({
                    id: r.id,
                    number: r.receipt_number,
                    po: r.purchase_order?.order_number,
                    supplier: r.supplier?.name,
                    date: r.receipt_date,
                    status: r.status,
                }))
            "
        >
            <template #cell-status="{ row }"><ErpStatusBadge :status="row.status" /></template>
            <template #cell-actions="{ row }">
                <Link :href="route('purchases.goods-receipts.show', row.id)" class="erp-link text-sm">Open</Link>
            </template>
        </ErpDataTable>
    </ErpLayout>
</template>