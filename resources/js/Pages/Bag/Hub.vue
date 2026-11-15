<script setup>
import ErpModuleIntro from '@/Components/Erp/ErpModuleIntro.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    summary: Object,
    recentBatches: Array,
    links: Array,
    canManage: Boolean,
});
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Shopping bag production"
            help-text="Release a production order, run batches on machines, separate good and rejected pieces, then post finished bags to inventory."
        >
            <template #actions>
                <Link v-if="canManage" :href="route('bag.orders.create')" class="erp-btn-primary">+ New order</Link>
            </template>
        </ErpPageHeader>

        <ErpModuleIntro
            title="Bag production steps"
            :steps="[
                'Define products, variants, and the active bill of materials (BOM).',
                'Create a production order when the factory should make quantity.',
                'Record batch output and post — Dana and additives are consumed, saleable bags increase stock.',
            ]"
        />

        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="erp-card p-4">
                <p class="text-sm text-erp-muted">Posted batches</p>
                <p class="text-2xl font-semibold">{{ summary.posted_batches }}</p>
            </div>
            <div class="erp-card p-4">
                <p class="text-sm text-erp-muted">MTD good bags</p>
                <p class="text-2xl font-semibold">{{ summary.good_bags_mtd }}</p>
            </div>
            <div class="erp-card p-4">
                <p class="text-sm text-erp-muted">Open orders</p>
                <p class="text-2xl font-semibold">{{ summary.open_orders }}</p>
            </div>
            <div class="erp-card p-4">
                <p class="text-sm text-erp-muted">Draft batches</p>
                <p class="text-2xl font-semibold">{{ summary.draft_batches }}</p>
            </div>
        </div>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="item in links"
                :key="item.href"
                :href="item.href"
                class="erp-card block p-5 transition hover:ring-2 hover:ring-erp-primary/30"
            >
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">{{ item.label }}</h3>
                <p class="mt-1 text-sm text-erp-muted">{{ item.description }}</p>
            </Link>
        </div>

        <div class="erp-card p-4">
            <h3 class="text-base font-semibold">Recent batches</h3>
            <ul class="mt-3 divide-y divide-slate-100 dark:divide-slate-800">
                <li v-for="batch in recentBatches" :key="batch.id" class="flex items-center justify-between py-2 text-sm">
                    <Link :href="route('bag.batches.show', batch.id)" class="font-medium text-erp-primary hover:underline">
                        {{ batch.batch_number }}
                    </Link>
                    <span class="flex items-center gap-2">
                        <span class="text-erp-muted">{{ batch.good_quantity }} pcs</span>
                        <ErpStatusBadge :status="batch.status" />
                    </span>
                </li>
                <li v-if="!recentBatches?.length" class="py-4 text-sm text-erp-muted">No batches yet.</li>
            </ul>
        </div>
    </ErpLayout>
</template>