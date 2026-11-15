<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, orders: Array, reportLinks: Array });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Production planning" />
        <div class="mb-4 flex flex-wrap gap-2">
            <Link v-for="l in reportLinks" :key="l.href" :href="l.href" class="erp-btn-secondary text-sm">{{ l.label }}</Link>
        </div>
        <ErpDataTable
            title="Open orders"
            :columns="[
                { key: 'order', label: 'Order' },
                { key: 'product', label: 'Product' },
                { key: 'remaining', label: 'Remaining' },
                { key: 'status', label: 'Status' },
            ]"
            :rows="
                orders.map((o) => ({
                    order: o.order_number,
                    product: o.product?.name,
                    remaining: o.planned_quantity - o.produced_quantity,
                    status: o.status,
                    href: route('bag.orders.show', o.id),
                }))
            "
        />
    </ErpLayout>
</template>