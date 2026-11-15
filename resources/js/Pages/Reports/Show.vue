<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    reportKey: String,
    definition: Object,
    title: String,
    description: String,
    columns: Array,
    rows: Array,
    filters: Object,
    summary: Object,
    totalRows: Number,
    glAccounts: Array,
    exportUrls: Object,
});

const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');
const asOf = ref(props.filters.as_of ?? '');
const granularity = ref(props.filters.granularity ?? 'day');
const search = ref(props.filters.search ?? '');
const glAccountId = ref(props.filters.gl_account_id ?? '');
const sortKey = ref(props.filters.sort ?? '');
const sortDirection = ref(props.filters.direction ?? 'asc');

const summaryEntries = computed(() => Object.entries(props.summary ?? {}));

const buildQuery = () => ({
    from: from.value || undefined,
    to: to.value || undefined,
    as_of: asOf.value || undefined,
    granularity: granularity.value,
    search: search.value || undefined,
    gl_account_id: glAccountId.value || undefined,
    sort: sortKey.value || undefined,
    direction: sortDirection.value,
    page: props.filters.page,
    per_page: props.filters.per_page,
});

const apply = () => router.get(route('erp.reports.show', props.reportKey), buildQuery(), { preserveState: true });

const onSort = ({ key, direction }) => {
    sortKey.value = key;
    sortDirection.value = direction;
    apply();
};

const openPrint = () => window.open(props.exportUrls.print, '_blank');
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="title" :description="description" />
        <div class="mb-4 flex flex-wrap items-end gap-2">
            <template v-if="definition.date_filter">
                <label class="text-sm">
                    <span class="text-erp-muted">From</span>
                    <input v-model="from" type="date" class="erp-input ml-1" />
                </label>
                <label class="text-sm">
                    <span class="text-erp-muted">To</span>
                    <input v-model="to" type="date" class="erp-input ml-1" />
                </label>
            </template>
            <label v-if="definition.as_of" class="text-sm">
                <span class="text-erp-muted">As of</span>
                <input v-model="asOf" type="date" class="erp-input ml-1" />
            </label>
            <label v-if="definition.granularity" class="text-sm">
                <span class="text-erp-muted">Group</span>
                <select v-model="granularity" class="erp-input ml-1">
                    <option value="day">Daily</option>
                    <option value="month">Monthly</option>
                </select>
            </label>
            <label v-if="definition.gl_account" class="text-sm">
                <span class="text-erp-muted">GL account</span>
                <select v-model="glAccountId" class="erp-input ml-1 min-w-[12rem]">
                    <option value="">Select…</option>
                    <option v-for="a in glAccounts" :key="a.id" :value="a.id">{{ a.code }} {{ a.name }}</option>
                </select>
            </label>
            <input v-model="search" type="search" placeholder="Search…" class="erp-input" />
            <button type="button" class="erp-btn-secondary" @click="apply">Apply</button>
            <a :href="exportUrls.csv" class="erp-btn-secondary">Excel (CSV)</a>
            <button type="button" class="erp-btn-secondary" @click="openPrint">Print / PDF</button>
        </div>
        <div v-if="summaryEntries.length" class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="[k, v] in summaryEntries" :key="k" class="erp-card p-3 text-sm">
                <span class="text-erp-muted">{{ k.replaceAll('_', ' ') }}</span>
                <div class="text-lg font-semibold">{{ v }}</div>
            </div>
        </div>
        <p class="mb-2 text-xs text-erp-muted">Showing {{ rows.length }} of {{ totalRows }} rows</p>
        <ErpDataTable
            :columns="columns"
            :rows="rows"
            :searchable="false"
            :sort-key="sortKey"
            :sort-direction="sortDirection"
            @sort="onSort"
        />
    </ErpLayout>
</template>