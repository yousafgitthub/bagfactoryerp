<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpFormField from '@/Components/Erp/ErpFormField.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    rows: Array,
    totals: Object,
    filters: Object,
});

const localFilters = reactive({ ...props.filters });

const apply = () => router.get(route('dana.reports.costing'), localFilters);
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Production costing report" />

        <form class="erp-card mb-4 grid gap-3 p-4 sm:grid-cols-2" @submit.prevent="apply">
            <p class="sm:col-span-2 text-xs text-erp-muted">Dates are optional — leave empty to include all posted batches.</p>
            <ErpFormField label="From date" name="date_from" help="Optional reporting period start.">
                <template #default="{ describedBy }">
                    <input id="date_from" v-model="localFilters.date_from" type="date" class="erp-input w-full" :aria-describedby="describedBy" />
                </template>
            </ErpFormField>
            <ErpFormField label="To date" name="date_to" help="Optional reporting period end.">
                <template #default="{ describedBy }">
                    <input id="date_to" v-model="localFilters.date_to" type="date" class="erp-input w-full" :aria-describedby="describedBy" />
                </template>
            </ErpFormField>
            <button type="submit" class="erp-btn-secondary sm:col-span-2">Apply range</button>
        </form>

        <ErpDataTable
            :columns="[
                { key: 'batch_number', label: 'Batch' },
                { key: 'machine', label: 'Machine' },
                { key: 'output_kg', label: 'Output KG' },
                { key: 'total_batch_cost', label: 'Total cost' },
                { key: 'cost_per_kg', label: 'Cost / KG' },
            ]"
            :rows="rows"
        />

        <p class="mt-4 text-sm text-erp-muted">
            Period total: {{ totals.output_kg }} KG · {{ totals.total_batch_cost }} cost
        </p>
    </ErpLayout>
</template>