<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    order: Object,
    allowedActions: Array,
    canManage: Boolean,
});

const workflow = (action) => router.post(route('bag.orders.workflow', { order: props.order.id, action }));
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="order.order_number">
            <template #actions>
                <Link v-if="canManage && order.status === 'draft'" :href="route('bag.orders.edit', order.id)" class="erp-btn-secondary">Edit</Link>
                <Link v-if="canManage && ['released', 'in_progress'].includes(order.status)" :href="route('bag.batches.create', { order_id: order.id })" class="erp-btn-primary">+ Batch</Link>
                <button v-for="action in allowedActions" :key="action" type="button" class="erp-btn-secondary" @click="workflow(action)">{{ action }}</button>
            </template>
        </ErpPageHeader>
        <div class="erp-card mb-4 p-4 text-sm">
            <ErpStatusBadge :status="order.status" />
            <span class="ml-2">Planned {{ order.planned_quantity }} · Produced {{ order.produced_quantity }} · Rejected {{ order.rejected_quantity }}</span>
            <p v-if="order.bom_version_snapshot" class="text-erp-muted">BOM v{{ order.bom_version_snapshot }}</p>
        </div>
        <ErpDataTable
            title="Batches"
            :columns="[
                { key: 'batch', label: 'Batch' },
                { key: 'good', label: 'Good' },
                { key: 'status', label: 'Status' },
            ]"
            :rows="
                (order.batches ?? []).map((b) => ({
                    batch: b.batch_number,
                    good: b.good_quantity,
                    status: b.status,
                    href: route('bag.batches.show', b.id),
                }))
            "
        />
    </ErpLayout>
</template>