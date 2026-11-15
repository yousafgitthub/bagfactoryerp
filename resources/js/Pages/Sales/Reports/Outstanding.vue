<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, invoices: Array, customerBalances: Array });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Outstanding receivables" />
        <ErpDataTable
            title="Open invoices"
            :columns="[
                { key: 'invoice', label: 'Invoice' },
                { key: 'customer', label: 'Customer' },
                { key: 'outstanding', label: 'Outstanding' },
            ]"
            :rows="invoices.map((i) => ({ invoice: i.invoice_number, customer: i.customer?.name, outstanding: i.outstanding }))"
        />
        <ErpDataTable
            class="mt-6"
            title="Customer balances"
            :columns="[
                { key: 'code', label: 'Code' },
                { key: 'name', label: 'Name' },
                { key: 'balance', label: 'Receivable' },
            ]"
            :rows="customerBalances.map((c) => ({ code: c.code, name: c.name, balance: c.current_balance }))"
        />
    </ErpLayout>
</template>