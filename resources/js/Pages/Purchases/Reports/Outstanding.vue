<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, invoices: Array, supplierBalances: Array });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Outstanding payables" />
        <ErpDataTable
            class="mb-6"
            title="Open invoices"
            :columns="[
                { key: 'invoice', label: 'Invoice' },
                { key: 'supplier', label: 'Supplier' },
                { key: 'due', label: 'Due' },
                { key: 'outstanding', label: 'Outstanding' },
                { key: 'actions', label: '' },
            ]"
            :rows="
                invoices.map((inv) => ({
                    invoice: inv.invoice_number,
                    supplier: inv.supplier?.name,
                    due: inv.due_date,
                    outstanding: inv.outstanding,
                    id: inv.id,
                }))
            "
        >
            <template #cell-actions="{ row }">
                <Link :href="route('purchases.invoices.show', row.id)" class="erp-link text-sm">View</Link>
            </template>
        </ErpDataTable>
        <ErpDataTable
            title="Supplier balances"
            :columns="[
                { key: 'code', label: 'Code' },
                { key: 'name', label: 'Supplier' },
                { key: 'balance', label: 'Balance' },
            ]"
            :rows="
                supplierBalances.map((s) => ({
                    code: s.code,
                    name: s.name,
                    balance: s.current_balance,
                }))
            "
        />
    </ErpLayout>
</template>