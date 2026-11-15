<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, order: Object, canManage: Boolean });

const confirm = () => {
    if (confirm('Confirm this sales order?')) router.post(route('sales.orders.confirm', props.order.id));
};
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="order.order_number">
            <template #actions>
                <ErpStatusBadge :status="order.status" class="mr-2" />
                <button v-if="canManage && order.status === 'draft'" type="button" class="erp-btn-primary" @click="confirm">Confirm</button>
            </template>
        </ErpPageHeader>
        <ErpDataTable
            :columns="[
                { key: 'product', label: 'Product' },
                { key: 'qty', label: 'Qty' },
                { key: 'invoiced', label: 'Invoiced' },
                { key: 'total', label: 'Line total' },
            ]"
            :rows="
                (order.lines || []).map((l) => ({
                    product: l.material?.name,
                    qty: l.ordered_qty,
                    invoiced: l.invoiced_qty,
                    total: l.line_total,
                }))
            "
        />
    </ErpLayout>
</template>