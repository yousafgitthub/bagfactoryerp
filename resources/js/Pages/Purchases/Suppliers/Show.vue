<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, supplier: Object, ledger: Object, canManage: Boolean });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="supplier.name">
            <template #actions>
                <Link v-if="canManage" :href="route('purchases.suppliers.edit', supplier.id)" class="erp-btn-secondary">Edit</Link>
                <Link :href="route('purchases.payments.create', { supplier_id: supplier.id })" class="erp-btn-primary">Record payment</Link>
            </template>
        </ErpPageHeader>
        <div class="mb-4 grid gap-4 sm:grid-cols-3">
            <div class="erp-card p-4 text-sm"><div class="text-erp-muted">Code</div>{{ supplier.code }}</div>
            <div class="erp-card p-4 text-sm"><div class="text-erp-muted">Balance</div><strong>{{ supplier.current_balance }}</strong></div>
            <div class="erp-card p-4 text-sm"><div class="text-erp-muted">Status</div><ErpStatusBadge :status="supplier.status" /></div>
        </div>
        <ErpDataTable
            title="Ledger"
            :columns="[
                { key: 'date', label: 'Date' },
                { key: 'type', label: 'Type' },
                { key: 'doc', label: 'Document' },
                { key: 'debit', label: 'Debit' },
                { key: 'credit', label: 'Credit' },
                { key: 'balance', label: 'Balance' },
            ]"
            :rows="
                (ledger.data || []).map((e) => ({
                    date: e.entry_date,
                    type: e.entry_type,
                    doc: e.document_number,
                    debit: e.debit,
                    credit: e.credit,
                    balance: e.running_balance,
                }))
            "
        />
    </ErpLayout>
</template>