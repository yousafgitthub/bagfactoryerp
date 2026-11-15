<script setup>
import ErpAlertList from '@/Components/Erp/ErpAlertList.vue';
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpProductionChart from '@/Components/Erp/ErpProductionChart.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpSummaryCard from '@/Components/Erp/ErpSummaryCard.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    pageTitle: {
        type: String,
        default: 'Dashboard',
    },
    breadcrumbs: {
        type: Array,
        default: () => [{ label: 'Dashboard' }],
    },
    dashboard: {
        type: Object,
        required: true,
    },
});

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
</script>

<template>
    <Head title="Dashboard" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Factory overview"
            description="Operational snapshot from live database tables. Transactional modules populate these widgets in later phases."
        >
            <template #actions>
                <span class="erp-btn-secondary pointer-events-none opacity-80">Role: {{ dashboard.team?.your_role ?? '—' }}</span>
            </template>
        </ErpPageHeader>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <ErpSummaryCard
                v-for="card in summaryCards"
                :key="card.key"
                :label="card.label"
                :value="card.value"
                :hint="card.hint"
                :trend="card.trend"
            />
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-3">
            <section class="erp-card p-5 lg:col-span-2">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">Production (7 days)</h3>
                <p class="mt-1 text-sm text-erp-muted">
                    Dana kilograms and bag pieces posted per day. Values read from batch tables when present.
                </p>
                <div class="mt-4">
                    <ErpProductionChart :points="productionPoints" />
                </div>
            </section>

            <section class="erp-card p-5">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">Inventory alerts</h3>
                <p class="mt-1 text-sm text-erp-muted">Below reorder level materials.</p>
                <div class="mt-4">
                    <ErpAlertList
                        :items="dashboard.inventory_alerts ?? []"
                        empty-title="No low-stock alerts. Inventory ledger not configured yet."
                    />
                </div>
            </section>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-3">
            <section class="erp-card p-5 lg:col-span-2">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">Recent stock movements</h3>
                <p class="mt-1 text-sm text-erp-muted">Last entries from the inventory ledger when available.</p>
                <div class="mt-4">
                    <ErpDataTable
                        :columns="[
                            { key: 'type', label: 'Type', sortable: true },
                            { key: 'reference', label: 'Reference', sortable: true },
                            { key: 'occurred_at', label: 'Date' },
                            { key: 'amount', label: 'Qty' },
                        ]"
                        :rows="transactionRows"
                        empty-message="No stock movements yet"
                        empty-description="Inventory posting is implemented in Phase 2."
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
                        <div class="flex justify-between gap-2">
                            <dt class="text-erp-muted">Last period</dt>
                            <dd class="font-medium">{{ dashboard.payroll_overview?.last_run_period ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div>
                    <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">Financial snapshot</h3>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-2">
                            <dt class="text-erp-muted">Cash on hand</dt>
                            <dd class="font-medium tabular-nums">
                                {{
                                    dashboard.financial_overview?.cash_on_hand === null
                                        ? '—'
                                        : dashboard.financial_overview.cash_on_hand
                                }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-erp-muted">Receivables</dt>
                            <dd class="font-medium tabular-nums">
                                {{
                                    dashboard.financial_overview?.receivables === null
                                        ? '—'
                                        : dashboard.financial_overview.receivables
                                }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-erp-muted">Payables</dt>
                            <dd class="font-medium tabular-nums">
                                {{
                                    dashboard.financial_overview?.payables === null
                                        ? '—'
                                        : dashboard.financial_overview.payables
                                }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>
        </div>

        <section v-if="machineRows.length" class="erp-card mt-6 p-5">
            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">Machine performance</h3>
            <div class="mt-4">
                <ErpDataTable
                    :columns="[
                        { key: 'name', label: 'Machine', sortable: true },
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