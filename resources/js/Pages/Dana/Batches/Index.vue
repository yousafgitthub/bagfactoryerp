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
        <ErpPageHeader
            title="Dana batches"
            help-text="Filter by status or machine. Open a batch to advance workflow steps or post to inventory."
        >
            <template #actions>
                <Link v-if="canManage" :href="route('dana.batches.create')" class="erp-btn-primary">+ New batch</Link>
            </template>
        </ErpPageHeader>

        <form class="erp-card mb-4 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3" @submit.prevent="applyFilters">
            <p class="sm:col-span-2 lg:col-span-3 text-xs text-erp-muted">All filters are optional — leave blank to list every batch.</p>
            <ErpFormField label="Batch number" name="batch_number" help="Optional — partial number match.">
                <template #default="{ describedBy }">
                    <input
                        id="batch_number"
                        v-model="localFilters.batch_number"
                        class="erp-input w-full"
                        placeholder="e.g. DB-2026-001"
                        :aria-describedby="describedBy"
                    />
                </template>
            </ErpFormField>
            <ErpFormField label="Status" name="status" help="Optional — defaults to all workflow states.">
                <template #default="{ describedBy }">
                    <select id="status" v-model="localFilters.status" class="erp-input w-full" :aria-describedby="describedBy">
                        <option value="">All statuses</option>
                        <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                    </select>
                </template>
            </ErpFormField>
            <ErpFormField label="Machine" name="machine_id" help="Optional — restrict to one machine.">
                <template #default="{ describedBy }">
                    <select id="machine_id" v-model="localFilters.machine_id" class="erp-input w-full" :aria-describedby="describedBy">
                        <option value="">All machines</option>
                        <option v-for="m in machines" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </template>
            </ErpFormField>
            <ErpFormField label="Color" name="color_id" help="Optional — filter by Dana color.">
                <template #default="{ describedBy }">
                    <select id="color_id" v-model="localFilters.color_id" class="erp-input w-full" :aria-describedby="describedBy">
                        <option value="">All colors</option>
                        <option v-for="c in colors" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </template>
            </ErpFormField>
            <ErpFormField label="From date" name="date_from" help="Optional — production date range start.">
                <template #default="{ describedBy }">
                    <input id="date_from" v-model="localFilters.date_from" type="date" class="erp-input w-full" :aria-describedby="describedBy" />
                </template>
            </ErpFormField>
            <ErpFormField label="To date" name="date_to" help="Optional — production date range end.">
                <template #default="{ describedBy }">
                    <input id="date_to" v-model="localFilters.date_to" type="date" class="erp-input w-full" :aria-describedby="describedBy" />
                </template>
            </ErpFormField>
            <button type="submit" class="erp-btn-secondary sm:col-span-2 lg:col-span-3">Apply filters</button>
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