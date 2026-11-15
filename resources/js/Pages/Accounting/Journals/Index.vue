<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, entries: Object });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Journal entries"
            help-text="Automatic postings from ERP modules and manual adjustments. Open a journal to see debits and credits."
        />
        <ErpDataTable
            :columns="[
                { key: 'number', label: 'JE#' },
                { key: 'date', label: 'Date' },
                { key: 'description', label: 'Description' },
                { key: 'debit', label: 'Debit' },
                { key: 'credit', label: 'Credit' },
            ]"
            empty-message="No journal entries yet."
            empty-description="Journals appear when you post inventory, sales, purchases, payroll, or expenses."
            :rows="
                (entries.data || []).map((e) => ({
                    number: e.entry_number,
                    date: e.entry_date,
                    description: e.description,
                    debit: e.total_debit,
                    credit: e.total_credit,
                    href: route('accounting.journals.show', e.id),
                }))
            "
        />
    </ErpLayout>
</template>