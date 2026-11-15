<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    pageTitle: { type: String, required: true },
    breadcrumbs: { type: Array, default: () => [] },
    title: { type: String, required: true },
    description: { type: String, default: '' },
    items: { type: Array, default: () => [] },
});
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="title" :description="description" />

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="item in items"
                :key="item.slug"
                :href="item.href"
                class="erp-card block p-5 transition hover:ring-2 hover:ring-erp-primary/30"
            >
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">{{ item.label }}</h3>
                <p class="mt-1 text-sm text-erp-muted">Search, filter, and maintain master records.</p>
            </Link>
        </div>

        <p v-if="!items.length" class="mt-6 text-sm text-erp-muted">No master data modules are available for your role.</p>
    </ErpLayout>
</template>