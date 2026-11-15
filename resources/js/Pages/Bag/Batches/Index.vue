<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    batches: Object,
    filters: Object,
    statuses: Object,
    machines: Array,
    canManage: Boolean,
});

const localFilters = reactive({ ...props.filters });

const applyFilters = () => router.get(route('bag.batches.index'), localFilters, { preserveState: true });
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Bag batches">
            <template #actions>
                <Link v-if="canManage" :href="route('bag.batches.create')" class="erp-btn-primary">+ New batch</Link>
            </template>
        </ErpPageHeader>

        <form class="erp-card mb-4 grid gap-3 p-4 sm:grid-cols-3" @submit.prevent="applyFilters">
            <select v-model="localFilters.status" class="erp-input">
                <option value="">All statuses</option>
                <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
            </select>
            <select v-model="localFilters.machine_id" class="erp-input">
                <option value="">All machines</option>
                <option v-for="m in machines" :key="m.id" :value="m.id">{{ m.name }}</option>
            </select>
            <button type="submit" class="erp-btn-secondary">Filter</button>
        </form>

        <ErpDataTable
            title="Batches"
            :columns="[
                { key: 'number', label: 'Batch' },
                { key: 'order', label: 'Order' },
                { key: 'good', label: 'Good' },
                { key: 'status', label: 'Status' },
            ]"
            :rows="
                (batches.data ?? []).map((b) => ({
                    number: b.batch_number,
                    order: b.order?.variant?.product?.name,
                    good: b.good_quantity,
                    status: b.status,
                    href: route('bag.batches.show', b.id),
                }))
            "
        />
    </ErpLayout>
</template>