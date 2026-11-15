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
});
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Employees"
            help-text="Maintain staff profiles, salary rates, and who is assigned to machines or production batches."
        >
            <template #actions>
                <Link v-if="canManage" :href="route('hr.employees.create')" class="erp-btn-primary">+ Employee</Link>
            </template>
        </ErpPageHeader>

        <ErpModuleIntro
            title="People on the floor"
            :steps="[
                'Add or update employee records and pay rates before payroll runs.',
                'Link staff to machines, departments, or batches when tracking output.',
                'Use the directory to filter by department or status.',
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