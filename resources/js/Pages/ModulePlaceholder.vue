<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpEmptyState from '@/Components/Erp/ErpEmptyState.vue';
import ErpFormSection from '@/Components/Erp/ErpFormSection.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    pageTitle: String,
    module: String,
    moduleLabel: String,
    breadcrumbs: Array,
});
</script>

<template>
    <Head :title="moduleLabel" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            :title="moduleLabel"
            description="This module will be implemented in a later phase. The list below shows the shared ERP table and status patterns from Prompt 13."
        >
            <template #actions>
                <button type="button" class="erp-btn-secondary" disabled>New record</button>
                <button type="button" class="erp-btn-primary" disabled>Post transaction</button>
            </template>
        </ErpPageHeader>

        <div class="grid gap-4 lg:grid-cols-2">
            <ErpFormSection title="Module status" description="Placeholder until business workflows are wired.">
                <ErpEmptyState
                    title="Not implemented yet"
                    :description="`The ${module} workflow arrives in a later prompt. Permissions and navigation are already enforced.`"
                />
            </ErpFormSection>

            <ErpFormSection title="Design system preview" description="Standard table, filters, and status labels.">
                <ErpDataTable
                    :columns="[
                        { key: 'name', label: 'Name', sortable: true },
                        { key: 'status', label: 'Status', sortable: true },
                    ]"
                    :rows="[]"
                    empty-message="No master records"
                    empty-description="Master data CRUD is added in Prompt 02."
                    row-actions
                >
                    <template #toolbar-actions>
                        <button type="button" class="erp-btn-secondary text-xs" disabled>Export</button>
                    </template>
                    <template #row-actions>
                        <button type="button" class="text-xs font-medium text-erp-primary" disabled>View</button>
                    </template>
                    <template #cell-status>
                        <ErpStatusBadge status="draft" label="Planned" />
                    </template>
                </ErpDataTable>
            </ErpFormSection>
        </div>
    </ErpLayout>
</template>