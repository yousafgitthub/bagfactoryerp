<script setup>
import ErpAlertList from '@/Components/Erp/ErpAlertList.vue';
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpProductionChart from '@/Components/Erp/ErpProductionChart.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpSummaryCard from '@/Components/Erp/ErpSummaryCard.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    pageTitle: { type: String, default: 'Dashboard' },
    breadcrumbs: { type: Array, default: () => [{ label: 'Dashboard' }] },
    dashboard: { type: Object, required: true },
});

const period = ref(props.dashboard.filters?.period ?? 'week');
const from = ref(props.dashboard.filters?.from ?? '');
const to = ref(props.dashboard.filters?.to ?? '');

watch(
    () => props.dashboard.filters,
    (filters) => {
        period.value = filters?.period ?? 'week';
        from.value = filters?.from ?? '';
        to.value = filters?.to ?? '';
    },
);

const applyFilter = () => {
    router.get(
        route('dashboard'),
        { period: period.value, from: from.value, to: to.value },
        { preserveState: true, replace: true },
    );
};

const summaryCards = computed(() => props.dashboard.summary ?? []);
const productionPoints = computed(() => props.dashboard.production_trend?.points ?? []);
const transactionRows = computed(() =>
    (props.dashboard.recent_transactions ?? []).map((row, index) => ({
        id: index,
        type: row.type,
        reference: row.reference,
        occurred_at: row.occurred_at,
        amount: row.amount ?? '—',
    })),
);
const machineRows = computed(() => props.dashboard.machine_performance ?? []);
const machineProductionRows = computed(() =>
    (props.dashboard.machine_wise_production ?? []).map((row, index) => ({
        id: index,
        ...row,
    })),
);
const consumptionRows = computed(() =>
    (props.dashboard.material_consumption ?? []).map((row, index) => ({
        id: index,
        ...row,
    })),
);
const monthlyBars = computed(() => props.dashboard.monthly_sales_expenses ?? []);
</script>

<template>
    <Head title="Dashboard" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Factory overview"
            description="Operational KPIs query live tables. Transactional modules populate figures in later phases."
        >
            <template #actions>
                <div class="flex flex-wrap items-center gap-2">
                    <select v-model="period" class="erp-input text-sm" @change="applyFilter">
                        <option value="today">Today</option>
                        <option value="week">This week</option>
                        <option value="month">This month</option>
                        <option value="custom">Custom range</option>
                    </select>
                    <template v-if="period === 'custom'">
                        <input v-model="from" type="date" class="erp-input text-sm" />
                        <input v-model="to" type="date" class="erp-input text-sm" />
                        <button type="button" class="erp-btn-secondary text-sm" @click="applyFilter">Apply</button>
                    </template>
                </div>
            </template>
        </ErpPageHeader>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <ErpSummaryCard
                v-for="card in summaryCards"
                :key="card.key"
                :label="card.label"
                :value="card.value"
                :hint="card.hint"
            />
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-3">
            <section class="erp-card p-5 lg:col-span-2">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">Daily production trend</h3>
                <p class="mt-1 text-sm text-erp-muted">Dana KG vs shopping bag pieces for the selected period.</p>
                <div class="mt-4">
                    <ErpProductionChart :points="productionPoints" />
                </div>
                <p class="mt-3 text-sm text-erp-muted">
                    Period total — Dana: {{ dashboard.dana_vs_bags?.totals?.dana_kg ?? 0 }} KG · Bags:
                    {{ dashboard.dana_vs_bags?.totals?.bags_pcs ?? 0 }} pcs
                </p>
            </section>

            <section class="erp-card p-5">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">Waste</h3>
                <p class="mt-1 text-sm text-erp-muted">Quantity and % of production in range.</p>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-erp-muted">Waste qty</dt>
                        <dd class="font-medium tabular-nums">{{ dashboard.waste?.quantity ?? 0 }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-erp-muted">Waste %</dt>
                        <dd class="font-medium tabular-nums">{{ dashboard.waste?.percentage ?? 0 }}%</dd>
                    </div>
                </dl>
            </section>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            <section class="erp-card p-5">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">Monthly sales & expenses</h3>
                <div class="mt-4 space-y-2">
                    <div
                        v-for="row in monthlyBars"
                        :key="row.month"
                        class="grid grid-cols-[5rem_1fr_1fr] gap-2 text-xs"
                    >
                        <span class="text-erp-muted">{{ row.month }}</span>
                        <span class="tabular-nums">Sales {{ row.sales }}</span>
                        <span class="tabular-nums">Exp {{ row.expenses }}</span>
                    </div>
                    <p v-if="!monthlyBars.length" class="text-sm text-erp-muted">No invoice/expense tables yet.</p>
                </div>
            </section>

            <section class="erp-card p-5">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">Inventory alerts</h3>
                <div class="mt-4">
                    <ErpAlertList
                        :items="dashboard.inventory_alerts ?? []"
                        empty-title="No low-stock alerts yet."
                    />
                </div>
            </section>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            <section class="erp-card p-5">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">Machine-wise production</h3>
                <ErpDataTable
                    class="mt-4"
                    :columns="[
                        { key: 'machine', label: 'Machine' },
                        { key: 'dana_kg', label: 'Dana KG' },
                        { key: 'bags_pcs', label: 'Bags pcs' },
                    ]"
                    :rows="machineProductionRows"
                    :searchable="false"
                    empty-message="No machines configured"
                />
            </section>

            <section class="erp-card p-5">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">Material consumption</h3>
                <ErpDataTable
                    class="mt-4"
                    :columns="[
                        { key: 'material', label: 'Material' },
                        { key: 'quantity', label: 'Issued qty' },
                    ]"
                    :rows="consumptionRows"
                    :searchable="false"
                    empty-message="No consumption in range"
                />
            </section>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-3">
            <section class="erp-card p-5 lg:col-span-2">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">Recent stock movements</h3>
                <div class="mt-4">
                    <ErpDataTable
                        :columns="[
                            { key: 'type', label: 'Type' },
                            { key: 'reference', label: 'Reference' },
                            { key: 'occurred_at', label: 'Date' },
                            { key: 'amount', label: 'Qty' },
                        ]"
                        :rows="transactionRows"
                        :searchable="false"
                        empty-message="No stock movements yet"
                    />
                </div>
            </section>

            <section class="erp-card space-y-5 p-5">
                <div>
                    <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">Payroll overview</h3>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-2">
                            <dt class="text-erp-muted">Active employees</dt>
                            <dd class="font-medium tabular-nums">{{ dashboard.payroll_overview?.headcount ?? 0 }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-erp-muted">Draft payroll runs</dt>
                            <dd class="font-medium tabular-nums">{{ dashboard.payroll_overview?.draft_runs ?? 0 }}</dd>
                        </div>
                    </dl>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">Financial snapshot</h3>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-2">
                            <dt class="text-erp-muted">Cash</dt>
                            <dd class="font-medium tabular-nums">
                                {{ dashboard.financial_overview?.cash_on_hand === null ? '—' : dashboard.financial_overview.cash_on_hand }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>
        </div>

        <section v-if="machineRows.length" class="erp-card mt-6 p-5">
            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">Registered machines</h3>
            <div class="mt-4">
                <ErpDataTable
                    :columns="[
                        { key: 'name', label: 'Machine' },
                        { key: 'utilization_pct', label: 'Utilization %' },
                        { key: 'status', label: 'Status' },
                    ]"
                    :rows="machineRows"
                    :searchable="false"
                >
                    <template #cell-status="{ row }">
                        <ErpStatusBadge :status="row.status" />
                    </template>
                </ErpDataTable>
            </div>
        </section>
    </ErpLayout>
</template>