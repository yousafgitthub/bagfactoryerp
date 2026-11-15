<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, rows: Array, reportLinks: Array, filters: Object });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Machine-wise production" />
        <div class="mb-4 flex flex-wrap gap-2">
            <Link v-for="l in reportLinks" :key="l.href" :href="l.href" class="erp-btn-secondary text-sm">{{ l.label }}</Link>
        </div>
        <ErpDataTable
            title="Output by machine"
            :columns="[
                { key: 'machine', label: 'Machine' },
                { key: 'good', label: 'Good bags' },
                { key: 'rejected', label: 'Rejected' },
                { key: 'batches', label: 'Batches' },
            ]"
            :rows="
                rows.map((r) => ({
                    machine: r.machine?.name ?? r.machine_id,
                    good: r.good_bags,
                    rejected: r.rejected_bags,
                    batches: r.batch_count,
                }))
            "
        />
    </ErpLayout>
</template>