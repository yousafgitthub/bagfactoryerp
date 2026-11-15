<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpFormField from '@/Components/Erp/ErpFormField.vue';
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
        <ErpPageHeader
            title="Bag production batches"
            help-text="Each batch records machine output, consumption from the BOM, and good vs rejected quantities before posting."
        >
            <template #actions>
                <Link v-if="canManage" :href="route('bag.batches.create')" class="erp-btn-primary">+ New batch</Link>
            </template>
        </ErpPageHeader>

        <form class="erp-card mb-4 grid gap-3 p-4 sm:grid-cols-2" @submit.prevent="applyFilters">
            <p class="sm:col-span-2 text-xs text-erp-muted">All filters are optional — leave selections empty to show every batch.</p>
            <ErpFormField label="Status" name="status" help="Optional — narrow by workflow state.">
                <template #default="{ describedBy }">
                    <select id="status" v-model="localFilters.status" class="erp-input w-full" :aria-describedby="describedBy">
                        <option value="">All statuses</option>
                        <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                    </select>
                </template>
            </ErpFormField>
            <ErpFormField label="Machine" name="machine_id" help="Optional — show batches for one machine only.">
                <template #default="{ describedBy }">
                    <select id="machine_id" v-model="localFilters.machine_id" class="erp-input w-full" :aria-describedby="describedBy">
                        <option value="">All machines</option>
                        <option v-for="m in machines" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </template>
            </ErpFormField>
            <button type="submit" class="erp-btn-secondary sm:col-span-2">Apply filters</button>
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