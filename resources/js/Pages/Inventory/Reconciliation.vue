<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    mismatches: Array,
});
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Stock reconciliation"
            description="Balances should match summed ledger quantities per warehouse and material."
        />

        <p v-if="!mismatches.length" class="erp-card p-4 text-sm text-erp-muted">All balances reconcile with the ledger.</p>

        <div v-else class="erp-card p-4">
            <ErpDataTable
                :columns="[
                    { key: 'wh', label: 'Warehouse' },
                    { key: 'mat', label: 'Material' },
                    { key: 'bal', label: 'Balance' },
                    { key: 'led', label: 'Ledger sum' },
                    { key: 'delta', label: 'Delta' },
                ]"
                :rows="
                    mismatches.map((m) => ({
                        wh: m.warehouse_id,
                        mat: m.material_id,
                        bal: m.balance_qty,
                        led: m.ledger_qty,
                        delta: m.delta,
                    }))
                "
            />
        </div>
    </ErpLayout>
</template>