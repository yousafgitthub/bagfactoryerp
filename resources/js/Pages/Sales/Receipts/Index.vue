<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, receipts: Object, canManage: Boolean });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Customer receipts">
            <template #actions>
                <Link v-if="canManage" :href="route('sales.receipts.create')" class="erp-btn-primary">+ Receipt</Link>
            </template>
        </ErpPageHeader>
        <ErpDataTable
            :columns="[
                { key: 'number', label: 'Receipt' },
                { key: 'customer', label: 'Customer' },
                { key: 'date', label: 'Date' },
                { key: 'amount', label: 'Amount' },
            ]"
            :rows="
                (receipts.data || []).map((r) => ({
                    number: r.receipt_number,
                    customer: r.customer?.name,
                    date: r.receipt_date,
                    amount: r.amount,
                }))
            "
        />
    </ErpLayout>
</template>