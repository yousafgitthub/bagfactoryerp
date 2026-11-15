<script setup>
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
            title="Dana production"
            description="Convert raw plastic and colors into finished Dana with controlled posting to inventory."
        >
            <template #actions>
                <Link v-if="canManage" :href="route('dana.batches.create')" class="erp-btn-primary">
                    + New batch
                </Link>
            </template>
        </ErpPageHeader>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="erp-card p-4">
                <p class="text-sm text-erp-muted">Posted batches</p>
                <p class="text-2xl font-semibold">{{ summary.posted_batches }}</p>
            </div>
            <div class="erp-card p-4">
                <p class="text-sm text-erp-muted">MTD output (KG)</p>
                <p class="text-2xl font-semibold">{{ summary.output_kg_mtd }}</p>
            </div>
            <div class="erp-card p-4">
                <p class="text-sm text-erp-muted">Draft batches</p>
                <p class="text-2xl font-semibold">{{ summary.draft_batches }}</p>
            </div>
            <div class="erp-card p-4">
                <p class="text-sm text-erp-muted">In workflow</p>
                <p class="text-2xl font-semibold">{{ summary.in_progress }}</p>
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
            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">Recent batches</h3>
            <ul class="mt-3 divide-y divide-slate-100 dark:divide-slate-800">
                <li v-for="batch in recentBatches" :key="batch.id" class="flex items-center justify-between py-2 text-sm">
                    <Link :href="route('dana.batches.show', batch.id)" class="font-medium text-erp-primary hover:underline">
                        {{ batch.batch_number }}
                    </Link>
                    <span class="flex items-center gap-2">
                        <span class="text-erp-muted">{{ batch.output_kg }} KG</span>
                        <ErpStatusBadge :status="batch.status" />
                    </span>
                </li>
                <li v-if="!recentBatches?.length" class="py-4 text-sm text-erp-muted">No batches yet.</li>
            </ul>
        </div>
    </ErpLayout>
</template>