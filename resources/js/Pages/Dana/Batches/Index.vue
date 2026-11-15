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
    colors: Array,
    canManage: Boolean,
});

const localFilters = reactive({ ...props.filters });

const applyFilters = () => {
    router.get(route('dana.batches.index'), localFilters, { preserveState: true });
};
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Dana batches">
            <template #actions>
                <Link v-if="canManage" :href="route('dana.batches.create')" class="erp-btn-primary">+ New batch</Link>
            </template>
        </ErpPageHeader>

        <form class="erp-card mb-4 grid gap-3 p-4 sm:grid-cols-3 lg:grid-cols-6" @submit.prevent="applyFilters">
            <input v-model="localFilters.batch_number" class="erp-input" placeholder="Batch #" />
            <select v-model="localFilters.status" class="erp-input">
                <option value="">All statuses</option>
                <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
            </select>
            <select v-model="localFilters.machine_id" class="erp-input">
                <option value="">All machines</option>
                <option v-for="m in machines" :key="m.id" :value="m.id">{{ m.name }}</option>
            </select>
            <select v-model="localFilters.color_id" class="erp-input">
                <option value="">All colors</option>
                <option v-for="c in colors" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <input v-model="localFilters.date_from" type="date" class="erp-input" />
            <input v-model="localFilters.date_to" type="date" class="erp-input" />
            <button type="submit" class="erp-btn-secondary sm:col-span-3 lg:col-span-6">Filter</button>
        </form>

        <ErpDataTable
            title="Batches"
            :columns="[
                { key: 'number', label: 'Batch' },
                { key: 'date', label: 'Date' },
                { key: 'machine', label: 'Machine' },
                { key: 'output', label: 'Output KG' },
                { key: 'status', label: 'Status' },
            ]"
            :rows="
                (batches.data ?? []).map((b) => ({
                    number: b.batch_number,
                    date: b.production_date,
                    machine: b.machine?.name,
                    output: b.output_kg,
                    status: b.status,
                }))
            "
        />
    </ErpLayout>
</template>