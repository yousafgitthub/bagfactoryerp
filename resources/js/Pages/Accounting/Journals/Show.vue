<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, entry: Object });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            :title="entry.entry_number"
            :description="entry.description"
            help-text="Debit and credit lines must balance. Source documents in inventory, sales, or payroll link back from the memo."
        />
        <p class="mb-4 text-sm text-erp-muted">Posted {{ entry.posted_at }} — status {{ entry.status }}</p>
        <h3 class="mb-2 text-sm font-semibold text-slate-800 dark:text-slate-100">Journal lines</h3>
        <ErpDataTable
            :columns="[
                { key: 'account', label: 'Account' },
                { key: 'debit', label: 'Debit' },
                { key: 'credit', label: 'Credit' },
                { key: 'memo', label: 'Memo' },
            ]"
            :rows="
                (entry.lines || []).map((l) => ({
                    account: `${l.account?.code} ${l.account?.name}`,
                    debit: l.debit,
                    credit: l.credit,
                    memo: l.memo,
                }))
            "
        />
    </ErpLayout>
</template>