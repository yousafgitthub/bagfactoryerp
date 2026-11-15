<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    entries: Object,
});
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Movement history" description="Immutable stock ledger entries." />

        <div class="erp-card p-4">
            <ErpDataTable
                :columns="[
                    { key: 'date', label: 'When' },
                    { key: 'ref', label: 'Reference' },
                    { key: 'type', label: 'Movement' },
                    { key: 'material', label: 'Material' },
                    { key: 'qty', label: 'Qty' },
                ]"
                :rows="
                    entries.data.map((e) => ({
                        date: e.occurred_at,
                        ref: e.reference,
                        type: e.movement_type,
                        material: e.material?.name,
                        qty: e.quantity,
                    }))
                "
                empty-message="No ledger entries yet."
            />
        </div>
    </ErpLayout>
</template>