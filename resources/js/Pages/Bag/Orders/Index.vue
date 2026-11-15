<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, orders: Object, statuses: Object, canManage: Boolean });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Production orders">
            <template #actions>
                <Link v-if="canManage" :href="route('bag.orders.create')" class="erp-btn-primary">+ New order</Link>
            </template>
        </ErpPageHeader>
        <ErpDataTable
            title="Orders"
            :columns="[
                { key: 'number', label: 'Order' },
                { key: 'product', label: 'Product' },
                { key: 'planned', label: 'Planned' },
                { key: 'done', label: 'Produced' },
                { key: 'status', label: 'Status' },
            ]"
            :rows="
                (orders.data ?? []).map((o) => ({
                    number: o.order_number,
                    product: o.product?.name,
                    planned: o.planned_quantity,
                    done: o.produced_quantity,
                    status: o.status,
                    href: route('bag.orders.show', o.id),
                }))
            "
        />
    </ErpLayout>
</template>