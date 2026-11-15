<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    transactions: Object,
    filters: Object,
    transactionTypes: Object,
    canManage: Boolean,
});
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Stock movements">
            <template #actions>
                <Link v-if="canManage" :href="route('inventory.transactions.create')" class="erp-btn-primary">+ New</Link>
            </template>
        </ErpPageHeader>

        <div class="erp-card p-4">
            <ErpDataTable
                :columns="[
                    { key: 'number', label: 'Number' },
                    { key: 'type', label: 'Type' },
                    { key: 'date', label: 'Date' },
                    { key: 'status', label: 'Status' },
                    { key: 'actions', label: '' },
                ]"
                :rows="
                    transactions.data.map((t) => ({
                        id: t.id,
                        number: t.transaction_number,
                        type: transactionTypes[t.type] ?? t.type,
                        date: t.transaction_date,
                        status: t.status,
                    }))
                "
                empty-message="No movements yet."
            >
                <template #cell-status="{ row }">
                    <ErpStatusBadge :status="row.status" />
                </template>
                <template #cell-actions="{ row }">
                    <Link :href="route('inventory.transactions.show', row.id)" class="erp-link text-sm">Open</Link>
                </template>
            </ErpDataTable>
        </div>
    </ErpLayout>
</template>