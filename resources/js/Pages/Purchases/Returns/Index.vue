<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, returns: Object, canManage: Boolean });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Purchase returns">
            <template #actions>
                <Link v-if="canManage" :href="route('purchases.returns.create')" class="erp-btn-primary">+ Return</Link>
            </template>
        </ErpPageHeader>
        <ErpDataTable
            :columns="[
                { key: 'number', label: 'Return' },
                { key: 'supplier', label: 'Supplier' },
                { key: 'date', label: 'Date' },
                { key: 'total', label: 'Subtotal' },
                { key: 'status', label: 'Status' },
                { key: 'actions', label: '' },
            ]"
            :rows="
                (returns.data || []).map((r) => ({
                    id: r.id,
                    number: r.return_number,
                    supplier: r.supplier?.name,
                    date: r.return_date,
                    total: r.subtotal,
                    status: r.status,
                }))
            "
        >
            <template #cell-status="{ row }"><ErpStatusBadge :status="row.status" /></template>
            <template #cell-actions="{ row }">
                <Link :href="route('purchases.returns.show', row.id)" class="erp-link text-sm">Open</Link>
            </template>
        </ErpDataTable>
    </ErpLayout>
</template>