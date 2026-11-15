<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, returns: Object, canManage: Boolean });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Sales returns">
            <template #actions>
                <Link v-if="canManage" :href="route('sales.returns.create')" class="erp-btn-primary">+ Return</Link>
            </template>
        </ErpPageHeader>
        <ErpDataTable
            :columns="[
                { key: 'number', label: 'Return' },
                { key: 'customer', label: 'Customer' },
                { key: 'date', label: 'Date' },
                { key: 'total', label: 'Credit' },
                { key: 'status', label: 'Status' },
            ]"
            :rows="
                (returns.data || []).map((r) => ({
                    id: r.id,
                    number: r.return_number,
                    customer: r.customer?.name,
                    date: r.return_date,
                    total: r.grand_total,
                    status: r.status,
                }))
            "
        >
            <template #cell-number="{ row }">
                <Link :href="route('sales.returns.show', row.id)" class="erp-link">{{ row.number }}</Link>
            </template>
        </ErpDataTable>
    </ErpLayout>
</template>