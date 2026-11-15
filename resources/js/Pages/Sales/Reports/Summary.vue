<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, summary: Object, filters: Object });

const from = ref(props.filters.from);
const to = ref(props.filters.to);

const apply = () => router.get(route('sales.reports.summary'), { from: from.value, to: to.value });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Sales summary" />
        <div class="mb-4 flex gap-2">
            <input v-model="from" type="date" class="erp-input" />
            <input v-model="to" type="date" class="erp-input" />
            <button type="button" class="erp-btn-secondary" @click="apply">Apply</button>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="erp-card p-4">Invoices: <strong>{{ summary.invoice_count }}</strong></div>
            <div class="erp-card p-4">Revenue: <strong>{{ summary.revenue }}</strong></div>
            <div class="erp-card p-4">COGS (WAC): <strong>{{ summary.cogs }}</strong></div>
            <div class="erp-card p-4">Gross margin: <strong>{{ summary.gross_margin }}</strong></div>
        </div>
    </ErpLayout>
</template>