<script setup>
import ErpLayout from '@/Layouts/ErpLayout.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, filters: Object, report: Object });

const asOf = ref(props.filters.as_of ?? '');
const apply = () => router.get(route('accounting.reports.balance-sheet'), { as_of: asOf.value || undefined });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Balance sheet" />
        <div class="mb-4 flex gap-2">
            <input v-model="asOf" type="date" class="erp-input" />
            <button type="button" class="erp-btn-secondary" @click="apply">Apply</button>
        </div>
        <div class="erp-card space-y-2 p-6">
            <p>Assets: <strong>{{ report.assets }}</strong></p>
            <p>Liabilities: <strong>{{ report.liabilities }}</strong></p>
            <p>Equity (incl. P&amp;L): <strong>{{ report.equity }}</strong></p>
            <p class="text-sm text-erp-muted">Balanced: {{ report.balanced ? 'Yes' : 'No' }}</p>
        </div>
    </ErpLayout>
</template>