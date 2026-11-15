<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, links: Array, canManage: Boolean });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Accounting" description="General ledger, financial statements and journals." />
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Link v-for="item in links" :key="item.href" :href="item.href" class="erp-card block p-5 transition hover:ring-2 hover:ring-erp-primary/30">
                <h3 class="text-base font-semibold">{{ item.label }}</h3>
                <p class="mt-1 text-sm text-erp-muted">{{ item.description }}</p>
            </Link>
        </div>
    </ErpLayout>
</template>