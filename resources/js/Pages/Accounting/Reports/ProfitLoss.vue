<script setup>
import ErpLayout from '@/Layouts/ErpLayout.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, filters: Object, report: Object });

const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');
const apply = () => router.get(route('accounting.reports.profit-loss'), { from: from.value || undefined, to: to.value || undefined });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Profit & loss" />
        <div class="mb-4 flex gap-2">
            <input v-model="from" type="date" class="erp-input" />
            <input v-model="to" type="date" class="erp-input" />
            <button type="button" class="erp-btn-secondary" @click="apply">Apply</button>
        </div>
        <div class="erp-card space-y-2 p-6">
            <p>Revenue: <strong>{{ report.revenue }}</strong></p>
            <p>Expenses: <strong>{{ report.expenses }}</strong></p>
            <p>Net profit: <strong>{{ report.net_profit }}</strong></p>
        </div>
    </ErpLayout>
</template>