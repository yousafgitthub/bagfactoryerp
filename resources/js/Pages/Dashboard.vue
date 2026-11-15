<script setup>
import ErpLayout from '@/Layouts/ErpLayout.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    pageTitle: {
        type: String,
        default: 'Dashboard',
    },
    breadcrumbs: {
        type: Array,
        default: () => [{ label: 'Dashboard' }],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Factory overview"
            description="Welcome to Bag Factory ERP. Use the sidebar to open modules your role can access."
        />

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</p>
                <p class="mt-2 text-lg font-semibold text-slate-900">Foundation ready</p>
                <p class="mt-1 text-sm text-slate-600">
                    Manufacturing, inventory posting, and accounting workflows arrive in later phases.
                </p>
            </div>
        </div>
    </ErpLayout>
</template>