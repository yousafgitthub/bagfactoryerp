<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, rows: Array, reportLinks: Array });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Production costing" />
        <div class="mb-4 flex flex-wrap gap-2">
            <Link v-for="l in reportLinks" :key="l.href" :href="l.href" class="erp-btn-secondary text-sm">{{ l.label }}</Link>
        </div>
        <ErpDataTable
            title="Posted batches"
            :columns="[
                { key: 'batch', label: 'Batch' },
                { key: 'product', label: 'Product' },
                { key: 'good', label: 'Good' },
                { key: 'cost', label: 'Cost/bag' },
            ]"
            :rows="
                rows.map((r) => ({
                    batch: r.batch_number,
                    product: r.order?.variant?.product?.name,
                    good: r.good_quantity,
                    cost: r.cost_per_good_bag,
                }))
            "
        />
    </ErpLayout>
</template>