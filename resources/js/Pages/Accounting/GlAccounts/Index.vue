<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, accounts: Array });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Chart of accounts" />
        <ErpDataTable
            :columns="[
                { key: 'code', label: 'Code' },
                { key: 'name', label: 'Name' },
                { key: 'type', label: 'Type' },
                { key: 'postable', label: 'Postable' },
            ]"
            :rows="
                accounts.map((a) => ({
                    code: a.code,
                    name: a.name,
                    type: a.type,
                    postable: a.is_postable ? 'Yes' : 'No',
                }))
            "
        />
    </ErpLayout>
</template>