<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    pageTitle: { type: String, required: true },
    breadcrumbs: { type: Array, default: () => [] },
    links: { type: Array, default: () => [] },
    lowStockCount: { type: Number, default: 0 },
    canManage: { type: Boolean, default: false },
    transactionTypes: { type: Object, default: () => ({}) },
});
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Inventory"
            description="Materials, stock balances, and immutable movement ledger with draft vs posted control."
        >
            <template #actions>
                <Link
                    v-if="canManage"
                    :href="route('inventory.transactions.create')"
                    class="erp-btn-primary"
                >
                    + Stock movement
                </Link>
            </template>
        </ErpPageHeader>

        <div v-if="lowStockCount" class="erp-card mb-4 border-l-4 border-amber-400 p-4">
            <p class="text-sm font-medium text-amber-900 dark:text-amber-100">
                {{ lowStockCount }} material balance{{ lowStockCount === 1 ? '' : 's' }} below reorder level
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
    </ErpLayout>
</template>