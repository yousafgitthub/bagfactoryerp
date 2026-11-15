<script setup>
import ErpModuleIntro from '@/Components/Erp/ErpModuleIntro.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    links: Array,
    canManage: Boolean,
    canApprove: Boolean,
});
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Payroll"
            help-text="Run monthly payroll from attendance, apply advances and loans, then mark salaries paid when treasury pays out."
        >
            <template #actions>
                <Link v-if="canManage" :href="route('hr.payroll.runs.create')" class="erp-btn-primary">+ Payroll run</Link>
            </template>
        </ErpPageHeader>

        <ErpModuleIntro
            title="Payroll run"
            :steps="[
                'Create a payroll run for the period and generate payslip lines from attendance.',
                'Review totals, then approve when figures match your sign-off.',
                'Record salary payments against treasury when cash or bank is disbursed.',
            ]"
        />

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