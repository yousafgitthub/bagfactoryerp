<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, balances: Array, reportLinks: Array });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Finished bag stock" />
        <div class="mb-4 flex flex-wrap gap-2">
            <Link v-for="l in reportLinks" :key="l.href" :href="l.href" class="erp-btn-secondary text-sm">{{ l.label }}</Link>
        </div>
        <ErpDataTable
            title="Saleable finished goods"
            :columns="[
                { key: 'warehouse', label: 'Warehouse' },
                { key: 'material', label: 'Material' },
                { key: 'qty', label: 'Qty' },
                { key: 'avg', label: 'Avg cost' },
            ]"
            :rows="
                balances.map((b) => ({
                    warehouse: b.warehouse?.name,
                    material: b.material?.name,
                    qty: b.quantity,
                    avg: b.average_unit_cost,
                }))
            "
        />
    </ErpLayout>
</template>