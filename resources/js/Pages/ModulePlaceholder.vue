<script setup>
import ErpLayout from '@/Layouts/ErpLayout.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';

defineProps({
    pageTitle: String,
    module: String,
    moduleLabel: String,
    breadcrumbs: Array,
});
</script>

<template>
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            :title="moduleLabel"
            description="This module will be implemented in a later phase. Access is controlled by role permissions."
        />

        <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm">
            <p class="text-sm text-slate-600">
                Placeholder for
                <span class="font-medium text-slate-900">{{ module }}</span>
            </p>
        </div>
    </ErpLayout>
</template>