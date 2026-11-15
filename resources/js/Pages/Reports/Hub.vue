<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    categories: Object,
    reportsByCategory: Object,
});
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Reports & analytics"
            help-text="Central catalog — all figures from posted transactions and ledgers. Pick a report, set dates, then export or print."
        />
        <div v-for="(label, key) in categories" :key="key" class="mb-8">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-erp-muted">{{ label }}</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="item in reportsByCategory[key] ?? []"
                    :key="item.key"
                    :href="item.href"
                    class="erp-card block p-4 transition hover:ring-2 hover:ring-erp-primary/30"
                >
                    <h3 class="text-base font-semibold">{{ item.title }}</h3>
                    <p class="mt-1 text-sm text-erp-muted">{{ item.description }}</p>
                </Link>
            </div>
        </div>
    </ErpLayout>
</template>