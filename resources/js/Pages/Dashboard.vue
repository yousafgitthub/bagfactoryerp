<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpIcon from '@/Components/Erp/ErpIcon.vue';
import ErpProductionChart from '@/Components/Erp/ErpProductionChart.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpSummaryCard from '@/Components/Erp/ErpSummaryCard.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { useErpSearch } from '@/composables/useErpSearch';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    pageTitle: { type: String, default: 'Dashboard' },
    breadcrumbs: { type: Array, default: () => [{ label: 'Dashboard' }] },
    dashboard: { type: Object, required: true },
});

const page = usePage();
const { query } = useErpSearch();
const period = ref(props.dashboard.filters?.period ?? 'week');
const from = ref(props.dashboard.filters?.from ?? '');
const to = ref(props.dashboard.filters?.to ?? '');
const customOpen = ref(props.dashboard.filters?.period === 'custom');

watch(
    () => props.dashboard.filters,
    (filters) => {
        period.value = filters?.period ?? 'week';
        from.value = filters?.from ?? '';
        to.value = filters?.to ?? '';
    },
);

const isoDate = (date) => {
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${date.getFullYear()}-${month}-${day}`;
};

const threeMonthRange = () => {
    const end = new Date();
    const start = new Date();
    start.setMonth(start.getMonth() - 3);

    return { from: isoDate(start), to: isoDate(end) };
};

const isThreeMonth = computed(() => {
    if (period.value !== 'custom') {
        return false;
    }

    const range = threeMonthRange();

    return from.value === range.from && to.value === range.to;
});

const applyFilter = () => {
    router.get(
        route('dashboard'),
        { period: period.value, from: from.value, to: to.value },
        { preserveState: true, replace: true },
    );
};

const setPreset = (preset) => {
    if (preset === 'today' || preset === 'week' || preset === 'month') {
        customOpen.value = false;
        period.value = preset;
        applyFilter();

        return;
    }

    const range = threeMonthRange();
    period.value = 'custom';
    from.value = range.from;
    to.value = range.to;
    customOpen.value = false;
    applyFilter();
};

const applyCustom = () => {
    period.value = 'custom';
    applyFilter();
};

const chipClass = (active) =>
    active
        ? 'rounded-full bg-[#5468f5] px-3 py-1 text-white shadow-sm'
        : 'rounded-full px-3 py-1 text-slate-500 hover:bg-white hover:text-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white';

const summaryCards = computed(() => props.dashboard.summary ?? []);
const cardByKey = (key) => summaryCards.value.find((card) => card.key === key);

const heroSpecs = [
    { key: 'today_bag_production', tone: 'bg-gradient-to-br from-blue-500 to-indigo-600', icon: 'factory' },
    { key: 'raw_material_stock', tone: 'bg-gradient-to-br from-teal-400 to-cyan-600', icon: 'cube' },
    { key: 'finished_bag_stock', tone: 'bg-gradient-to-br from-violet-500 to-fuchsia-600', icon: 'bag' },
    { key: 'month_sales', tone: 'bg-gradient-to-br from-emerald-400 to-green-600', icon: 'cart' },
];

const heroCards = computed(() =>
    heroSpecs
        .map((spec) => {
            const card = cardByKey(spec.key);

            return card ? { ...spec, ...card } : null;
        })
        .filter(Boolean),
);

const heroKeys = new Set(heroSpecs.map((spec) => spec.key));
const otherCards = computed(() => summaryCards.value.filter((card) => !heroKeys.has(card.key)));

const formatQty = (value) => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    const number = Number(value);

    if (!Number.isFinite(number)) {
        return String(value);
    }

    return new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(number);
};

const formatWhen = (value) => {
    if (!value) {
        return '';
    }

    const parsed = new Date(String(value).replace(' ', 'T'));

    if (Number.isNaN(parsed.getTime())) {
        return String(value);
    }

    return parsed.toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
};

const movementTone = (type) => {
    const key = String(type ?? '').toLowerCase();

    if (key.includes('receipt')) {
        return 'success';
    }

    if (key.includes('issue')) {
        return 'warning';
    }

    return 'info';
};

const unitFor = (key) => {
    const label = cardByKey(key)?.label ?? '';

    if (/kg/i.test(label)) {
        return 'kg';
    }

    if (/pcs/i.test(label)) {
        return 'pcs';
    }

    return '';
};

const stockSlices = computed(() => {
    const parts = [
        { key: 'raw', label: 'Raw materials', value: Math.max(0, Number(cardByKey('raw_material_stock')?.value) || 0), color: '#3b82f6', unit: unitFor('raw_material_stock') },
        { key: 'dana', label: 'Dana stock', value: Math.max(0, Number(cardByKey('dana_stock_kg')?.value) || 0), color: '#14b8a6', unit: unitFor('dana_stock_kg') },
        { key: 'finished', label: 'Finished bags', value: Math.max(0, Number(cardByKey('finished_bag_stock')?.value) || 0), color: '#8b5cf6', unit: unitFor('finished_bag_stock') },
    ];
    const total = parts.reduce((sum, part) => sum + part.value, 0);

    return {
        total,
        parts: parts.map((part) => ({
            ...part,
            pct: total > 0 ? (part.value / total) * 100 : 0,
        })),
        finishedPct: total > 0 ? Math.round((parts[2].value / total) * 100) : 0,
    };
});

const donutSegments = computed(() => {
    const radius = 36;
    const circumference = 2 * Math.PI * radius;
    const visible = stockSlices.value.parts.filter((part) => part.value > 0);
    const gap = visible.length > 1 ? 3 : 0;
    let cursor = 0;

    return visible.map((part) => {
        const full = (part.pct / 100) * circumference;
        const length = Math.max(0, full - gap);
        const segment = {
            ...part,
            dasharray: `${length} ${circumference - length}`,
            dashoffset: -cursor,
        };
        cursor += full;

        return segment;
    });
});

const needle = computed(() => query.value.trim().toLowerCase());
const matches = (...parts) => !needle.value || parts.some((part) => String(part ?? '').toLowerCase().includes(needle.value));

const productionPoints = computed(() => props.dashboard.production_trend?.points ?? []);
const transactionRows = computed(() =>
    (props.dashboard.recent_transactions ?? [])
        .map((row, index) => ({
            id: index,
            type: row.type,
            reference: row.reference,
            occurred_at: row.occurred_at,
            amount: row.amount ?? '—',
        }))
        .filter((row) => matches(row.type, row.reference, row.amount, row.occurred_at)),
);
const alerts = computed(() =>
    (props.dashboard.inventory_alerts ?? []).filter((item) => matches(item.title, item.detail, item.severity)),
);
const machineRows = computed(() => props.dashboard.machine_performance ?? []);
const filteredMachineRows = computed(() =>
    machineRows.value.filter((row) => matches(row.name, row.utilization_pct, row.status)),
);
const machineProductionRows = computed(() =>
    (props.dashboard.machine_wise_production ?? [])
        .map((row, index) => ({ id: index, ...row }))
        .filter((row) => matches(row.machine, row.dana_kg, row.bags_pcs)),
);
const consumptionRows = computed(() =>
    (props.dashboard.material_consumption ?? [])
        .map((row, index) => ({ id: index, ...row }))
        .filter((row) => matches(row.material, row.quantity)),
);
const monthlyBars = computed(() => props.dashboard.monthly_sales_expenses ?? []);
const monthMax = computed(() =>
    Math.max(
        1,
        ...monthlyBars.value.flatMap((row) => [Number(row.sales) || 0, Number(row.expenses) || 0]),
    ),
);
const barWidth = (value) => `${Math.max(Number(value) > 0 ? 4 : 0, ((Number(value) || 0) / monthMax.value) * 100)}%`;

const permissions = computed(() => {
    const value = page.props.auth?.user?.permissions ?? [];

    return Array.isArray(value) ? value : Object.values(value);
});
const navigation = computed(() => page.props.navigation ?? []);
const can = (permission) => permissions.value.includes(permission);
const navHas = (key) => navigation.value.some((item) => item.key === key);

const productionHref = computed(() => {
    if (can('shopping-bag-production.manage') && route().has('bag.batches.create')) {
        return route('bag.batches.create');
    }

    if (can('dana-production.manage') && route().has('dana.batches.create')) {
        return route('dana.batches.create');
    }

    return null;
});

const quickActions = computed(() =>
    [
        {
            key: 'purchase',
            label: 'New Purchase',
            icon: 'cart',
            className: 'bg-violet-50 text-violet-700 hover:bg-violet-100 dark:bg-violet-950/40 dark:text-violet-200',
            href: can('purchases.manage') && route().has('purchases.orders.create') ? route('purchases.orders.create') : null,
        },
        {
            key: 'sales',
            label: 'New Sales Order',
            icon: 'bag',
            className: 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-200',
            href: can('sales.manage') && route().has('sales.orders.create') ? route('sales.orders.create') : null,
        },
        {
            key: 'production',
            label: 'New Production',
            icon: 'factory',
            className: 'bg-sky-50 text-sky-700 hover:bg-sky-100 dark:bg-sky-950/40 dark:text-sky-200',
            href: productionHref.value,
        },
        {
            key: 'reports',
            label: 'View Reports',
            icon: 'chart',
            className: 'bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-200',
            href: navHas('reports') && route().has('erp.reports') ? route('erp.reports') : null,
        },
    ].filter((action) => action.href),
);

const productsHref = computed(() =>
    navHas('shopping-bag-production') && route().has('bag.products.index') ? route('bag.products.index') : null,
);
const ledgerHref = computed(() => (navHas('inventory') && route().has('inventory.ledger.index') ? route('inventory.ledger.index') : null));
const stockHref = computed(() => (navHas('inventory') && route().has('inventory.balances.index') ? route('inventory.balances.index') : null));

const userName = computed(() => page.props.auth?.user?.name || 'there');
const listEmpty = (fallback) => (needle.value ? 'No matches in this list.' : fallback);
</script>

<template>
    <Head title="Dashboard" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <div class="grid items-center gap-4 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 sm:text-3xl">
                    Welcome back, <span class="text-[#3154d6] dark:text-indigo-300">{{ userName }}</span>!
                </h2>
                <p class="mt-2 text-sm text-erp-muted">
                    Today's production, stock, sales, and alerts — use the period filters below to compare weeks or months.
                </p>
                <div class="mt-3 h-1 w-16 rounded-full bg-[#5468f5]" />
            </div>

            <section class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#1e3a8a] via-[#2563eb] to-[#38bdf8] p-6 text-white shadow-lg lg:col-span-3">
                <div class="relative z-10 max-w-sm">
                    <h3 class="text-2xl font-bold leading-tight">Quality Bags.<br />Built for Your Business.</h3>
                    <p class="mt-2 text-sm text-white/85">Premium Plastic Bags &amp; Customized Solutions</p>
                    <Link
                        v-if="productsHref"
                        :href="productsHref"
                        class="mt-4 inline-flex items-center rounded-full bg-[#5b4dff] px-4 py-2 text-sm font-semibold shadow-md transition hover:bg-[#4b3ef0]"
                    >
                        View Products →
                    </Link>
                </div>
                <svg class="mt-4 h-24 w-full sm:pointer-events-none sm:absolute sm:inset-y-0 sm:right-0 sm:mt-0 sm:h-full sm:w-[46%]" viewBox="0 0 320 160" aria-hidden="true">
                    <g transform="translate(8 28)">
                        <path d="M18 36h46l6 78H12z" fill="#f472b6" />
                        <path d="M26 36c8-22 22-22 30 0" fill="none" stroke="#f472b6" stroke-width="6" stroke-linecap="round" />
                    </g>
                    <g transform="translate(62 18)">
                        <path d="M18 36h50l7 90H12z" fill="#4ade80" />
                        <path d="M26 36c9-24 24-24 34 0" fill="none" stroke="#4ade80" stroke-width="6" stroke-linecap="round" />
                    </g>
                    <g transform="translate(124 24)">
                        <path d="M18 36h48l6 84H12z" fill="#60a5fa" />
                        <path d="M26 36c8-22 22-22 30 0" fill="none" stroke="#60a5fa" stroke-width="6" stroke-linecap="round" />
                    </g>
                    <g transform="translate(186 16)">
                        <path d="M18 36h50l7 92H12z" fill="#fb923c" />
                        <path d="M26 36c9-24 24-24 34 0" fill="none" stroke="#fb923c" stroke-width="6" stroke-linecap="round" />
                    </g>
                    <g transform="translate(248 30)">
                        <path d="M16 34h42l5 74H11z" fill="#f8fafc" />
                        <path d="M24 34c7-20 20-20 28 0" fill="none" stroke="#e2e8f0" stroke-width="6" stroke-linecap="round" />
                    </g>
                </svg>
            </section>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="card in heroCards"
                :key="card.key"
                class="relative min-h-[9.5rem] overflow-hidden rounded-2xl p-5 pb-8 text-white shadow-lg transition duration-200 hover:-translate-y-0.5 hover:shadow-xl"
                :class="card.tone"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-white/90">{{ card.label }}</p>
                        <p class="mt-2 text-3xl font-bold tracking-tight tabular-nums">{{ formatQty(card.value) }}</p>
                        <p v-if="card.hint" class="mt-1 line-clamp-2 text-xs text-white/80">{{ card.hint }}</p>
                    </div>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/20">
                        <ErpIcon :name="card.icon" class="h-5 w-5" />
                    </span>
                </div>
                <svg class="pointer-events-none absolute inset-x-0 bottom-0 h-12 w-full" viewBox="0 0 240 48" preserveAspectRatio="none" aria-hidden="true">
                    <path d="M0 32 C40 32 60 12 100 18 S160 40 200 22 240 10 240 16" fill="none" stroke="white" stroke-width="3" opacity="0.45" />
                    <path d="M0 40 C50 28 80 36 120 30 S180 18 240 28" fill="none" stroke="white" stroke-width="2" opacity="0.3" />
                </svg>
            </article>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-2 xl:grid-cols-12">
            <section class="erp-card p-5 lg:col-span-2 xl:col-span-6">
                <div class="flex flex-col gap-3">
                    <div>
                        <h3 class="flex items-center gap-2 text-base font-semibold text-slate-900 dark:text-slate-50">
                            <ErpIcon name="chart" class="h-5 w-5 text-indigo-500" />
                            Production &amp; Sales Overview
                        </h3>
                        <p class="mt-1 text-xs text-erp-muted">
                            {{ dashboard.filters?.from }} – {{ dashboard.filters?.to }}.
                            Dana {{ formatQty(dashboard.dana_vs_bags?.totals?.dana_kg ?? 0) }} KG · Bags
                            {{ formatQty(dashboard.dana_vs_bags?.totals?.bags_pcs ?? 0) }} pcs.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-1 self-start rounded-full bg-slate-100 p-1 text-xs font-semibold dark:bg-slate-800">
                        <button type="button" :class="chipClass(period === 'today')" @click="setPreset('today')">Today</button>
                        <button type="button" :class="chipClass(period === 'week')" title="This week" @click="setPreset('week')">7 Days</button>
                        <button type="button" :class="chipClass(period === 'month')" title="This month" @click="setPreset('month')">30 Days</button>
                        <button type="button" :class="chipClass(isThreeMonth)" title="Last three months" @click="setPreset('3m')">3 Months</button>
                        <button type="button" :class="chipClass(period === 'custom' && !isThreeMonth)" @click="customOpen = true">Custom</button>
                    </div>
                </div>
                <div v-if="customOpen || (period === 'custom' && !isThreeMonth)" class="mt-3 flex flex-wrap items-center gap-2">
                    <input v-model="from" type="date" class="erp-input w-auto text-sm" />
                    <input v-model="to" type="date" class="erp-input w-auto text-sm" />
                    <button type="button" class="erp-btn-secondary text-sm" @click="applyCustom">Apply</button>
                </div>
                <div class="mt-4">
                    <ErpProductionChart :points="productionPoints" />
                </div>
            </section>

            <section class="erp-card p-5 xl:col-span-3">
                <h3 class="flex items-center gap-2 text-base font-semibold text-slate-900 dark:text-slate-50">
                    <ErpIcon name="cube" class="h-5 w-5 text-indigo-500" />
                    Inventory Overview
                </h3>
                <div class="mt-4 flex flex-col items-center gap-4 sm:flex-row xl:flex-col">
                    <div class="relative h-36 w-36 shrink-0">
                        <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90">
                            <circle cx="50" cy="50" r="36" fill="none" stroke="currentColor" stroke-width="12" class="text-slate-100 dark:text-slate-800" />
                            <circle
                                v-for="segment in donutSegments"
                                :key="segment.key"
                                cx="50"
                                cy="50"
                                r="36"
                                fill="none"
                                :stroke="segment.color"
                                stroke-width="12"
                                :stroke-dasharray="segment.dasharray"
                                :stroke-dashoffset="segment.dashoffset"
                            />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                            <span class="text-xl font-bold tabular-nums text-slate-900 dark:text-slate-50">{{ stockSlices.finishedPct }}%</span>
                            <span class="px-4 text-[10px] leading-tight text-erp-muted">{{ stockSlices.total > 0 ? 'Finished bags' : 'No stock' }}</span>
                        </div>
                    </div>
                    <ul class="w-full space-y-2 text-sm">
                        <li v-for="part in stockSlices.parts" :key="part.key" class="flex items-start gap-2">
                            <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: part.color }" />
                            <span class="min-w-0 flex-1">
                                <span class="block font-medium text-slate-800 dark:text-slate-100">{{ part.label }}</span>
                                <span class="text-xs text-erp-muted">{{ formatQty(part.value) }} {{ part.unit }} · {{ Math.round(part.pct) }}%</span>
                            </span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-amber-400" />
                            <span class="min-w-0 flex-1">
                                <span class="block font-medium text-slate-800 dark:text-slate-100">Low stock</span>
                                <span class="text-xs text-erp-muted">{{ dashboard.inventory_alerts?.length ?? 0 }} items</span>
                            </span>
                        </li>
                    </ul>
                </div>
            </section>

            <section class="erp-card p-5 xl:col-span-3">
                <h3 class="flex items-center gap-2 text-base font-semibold text-slate-900 dark:text-slate-50">
                    <ErpIcon name="bolt" class="h-5 w-5 text-indigo-500" />
                    Quick Actions
                </h3>
                <div v-if="quickActions.length" class="mt-4 grid grid-cols-2 gap-3">
                    <Link
                        v-for="action in quickActions"
                        :key="action.key"
                        :href="action.href"
                        class="flex flex-col items-center justify-center gap-2 rounded-2xl px-2 py-4 text-center text-sm font-semibold transition"
                        :class="action.className"
                    >
                        <ErpIcon :name="action.icon" class="h-6 w-6" />
                        {{ action.label }}
                    </Link>
                </div>
                <p v-else class="mt-4 text-sm text-erp-muted">No actions for this role.</p>
            </section>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            <section class="erp-card p-5">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="flex items-center gap-2 text-base font-semibold text-slate-900 dark:text-slate-50">
                        <ErpIcon name="receipt" class="h-5 w-5 text-indigo-500" />
                        Recent Transactions
                    </h3>
                    <Link v-if="ledgerHref" :href="ledgerHref" class="text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-300">
                        View all →
                    </Link>
                </div>
                <ul v-if="transactionRows.length" class="mt-2 divide-y divide-slate-100 dark:divide-slate-800">
                    <li v-for="row in transactionRows" :key="row.id" class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-200">
                                <ErpIcon name="receipt" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-50">{{ row.type }} · {{ row.reference }}</p>
                                <p class="text-xs text-erp-muted">{{ formatWhen(row.occurred_at) }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-3 sm:justify-end">
                            <span class="text-sm font-semibold tabular-nums">{{ formatQty(row.amount) }}</span>
                            <ErpStatusBadge :status="movementTone(row.type)" :label="row.type" />
                        </div>
                    </li>
                </ul>
                <p v-else class="mt-4 text-sm text-erp-muted">{{ listEmpty('No stock movements yet') }}</p>
            </section>

            <section class="erp-card p-5">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="flex items-center gap-2 text-base font-semibold text-slate-900 dark:text-slate-50">
                        <ErpIcon name="alert" class="h-5 w-5 text-rose-500" />
                        Low Stock Alerts
                    </h3>
                    <Link v-if="stockHref" :href="stockHref" class="text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-300">
                        View all →
                    </Link>
                </div>
                <ul v-if="alerts.length" class="mt-2 divide-y divide-slate-100 dark:divide-slate-800">
                    <li v-for="(item, index) in alerts" :key="index" class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-500 dark:bg-rose-950/40">
                                <ErpIcon name="box" class="h-5 w-5" />
                            </span>
                            <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-50">{{ item.title }}</p>
                        </div>
                        <div class="flex items-center justify-between gap-3 sm:justify-end">
                            <span class="text-sm text-erp-muted">{{ item.detail }}</span>
                            <ErpStatusBadge :status="item.severity ?? 'warning'" :label="item.severity ?? 'warning'" />
                        </div>
                    </li>
                </ul>
                <p v-else class="mt-4 text-sm text-erp-muted">{{ listEmpty('No low-stock alerts yet.') }}</p>
            </section>
        </div>

        <section class="mt-6">
            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">Other live figures</h3>
            <p class="mt-1 text-sm text-erp-muted">Remaining summary cards from the same dashboard payload.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <ErpSummaryCard
                    v-for="card in otherCards"
                    :key="card.key"
                    :label="card.label"
                    :value="formatQty(card.value)"
                    :hint="card.hint"
                />
            </div>
        </section>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            <section class="erp-card p-5">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">Monthly sales &amp; expenses</h3>
                <div class="mt-4 space-y-3">
                    <div v-for="row in monthlyBars" :key="row.month">
                        <div class="flex justify-between gap-3 text-xs">
                            <span class="text-erp-muted">{{ row.month }}</span>
                            <span class="tabular-nums">Sales {{ formatQty(row.sales) }} · Exp {{ formatQty(row.expenses) }}</span>
                        </div>
                        <div class="mt-1 flex h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <span class="bg-blue-500" :style="{ width: barWidth(row.sales) }" />
                            <span class="bg-amber-400" :style="{ width: barWidth(row.expenses) }" />
                        </div>
                    </div>
                    <p v-if="!monthlyBars.length" class="text-sm text-erp-muted">No invoice or expense rows yet.</p>
                </div>
            </section>

            <div class="grid gap-4">
                <section class="erp-card p-5">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">Waste</h3>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-2">
                            <dt class="text-erp-muted">Waste qty</dt>
                            <dd class="font-medium tabular-nums">{{ formatQty(dashboard.waste?.quantity ?? 0) }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-erp-muted">Waste %</dt>
                            <dd class="font-medium tabular-nums">{{ dashboard.waste?.percentage ?? 0 }}%</dd>
                        </div>
                    </dl>
                </section>
                <section class="erp-card grid gap-5 p-5 sm:grid-cols-2">
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
                                <dt class="text-erp-muted">Last run</dt>
                                <dd class="font-medium">{{ dashboard.payroll_overview?.last_run_period || '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">Financial snapshot</h3>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between gap-2">
                                <dt class="text-erp-muted">Cash</dt>
                                <dd class="font-medium tabular-nums">{{ formatQty(dashboard.financial_overview?.cash_on_hand) }}</dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-erp-muted">Receivables</dt>
                                <dd class="font-medium tabular-nums">{{ formatQty(dashboard.financial_overview?.receivables) }}</dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-erp-muted">Payables</dt>
                                <dd class="font-medium tabular-nums">{{ formatQty(dashboard.financial_overview?.payables) }}</dd>
                            </div>
                        </dl>
                    </div>
                </section>
            </div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            <section class="min-w-0">
                <h3 class="mb-3 text-base font-semibold text-slate-900 dark:text-slate-50">Machine-wise production</h3>
                <ErpDataTable
                    :columns="[
                        { key: 'machine', label: 'Machine' },
                        { key: 'dana_kg', label: 'Dana KG' },
                        { key: 'bags_pcs', label: 'Bags pcs' },
                    ]"
                    :rows="machineProductionRows"
                    :searchable="false"
                    :empty-message="listEmpty('No machines configured')"
                />
            </section>
            <section class="min-w-0">
                <h3 class="mb-3 text-base font-semibold text-slate-900 dark:text-slate-50">Material consumption</h3>
                <ErpDataTable
                    :columns="[
                        { key: 'material', label: 'Material' },
                        { key: 'quantity', label: 'Issued qty' },
                    ]"
                    :rows="consumptionRows"
                    :searchable="false"
                    :empty-message="listEmpty('No consumption in range')"
                />
            </section>
        </div>

        <section v-if="machineRows.length" class="mt-6 min-w-0">
            <h3 class="mb-3 text-base font-semibold text-slate-900 dark:text-slate-50">Registered machines</h3>
            <ErpDataTable
                :columns="[
                    { key: 'name', label: 'Machine' },
                    { key: 'utilization_pct', label: 'Utilization %' },
                    { key: 'status', label: 'Status' },
                ]"
                :rows="filteredMachineRows"
                :searchable="false"
                :empty-message="listEmpty('No machines configured')"
            >
                <template #cell-status="{ row }">
                    <ErpStatusBadge :status="row.status" />
                </template>
            </ErpDataTable>
        </section>
    </ErpLayout>
</template>
