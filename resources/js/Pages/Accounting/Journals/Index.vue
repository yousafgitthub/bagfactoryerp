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
        <ErpPageHeader title="Journal entries" />
        <ErpDataTable
            :columns="[
                { key: 'number', label: 'JE#' },
                { key: 'date', label: 'Date' },
                { key: 'description', label: 'Description' },
                { key: 'debit', label: 'Debit' },
                { key: 'credit', label: 'Credit' },
            ]"
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