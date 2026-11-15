<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    balances: Object,
    lowStock: Array,
});
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Current stock" description="Weighted average valuation per warehouse." />

        <div v-if="lowStock.length" class="erp-card mb-4 p-4">
            <h3 class="font-semibold text-amber-800 dark:text-amber-200">Low stock warnings</h3>
            <ul class="mt-2 text-sm">
                <li v-for="(row, i) in lowStock" :key="i">
                    {{ row.material?.name }} @ {{ row.warehouse?.name }} — {{ row.quantity }} (reorder {{ row.material?.reorder_level }})
                </li>
            </ul>
        </div>

        <div class="erp-card p-4">
            <ErpDataTable
                :columns="[
                    { key: 'material', label: 'Material' },
                    { key: 'warehouse', label: 'Warehouse' },
                    { key: 'qty', label: 'Quantity' },
                    { key: 'avg', label: 'Avg cost' },
                    { key: 'value', label: 'Value' },
                ]"
                :rows="
                    balances.data.map((b) => ({
                        material: b.material?.name,
                        warehouse: b.warehouse?.name,
                        qty: b.quantity,
                        avg: b.average_unit_cost,
                        value: b.total_value,
                    }))
                "
                empty-message="No stock balances."
            />
        </div>
    </ErpLayout>
</template>