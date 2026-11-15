<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpModuleIntro from '@/Components/Erp/ErpModuleIntro.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    batch: Object,
    statuses: Object,
    wasteTypes: Object,
    allowedActions: Array,
    previewCosts: Object,
    canManage: Boolean,
});

const workflow = (action) => router.post(route('dana.batches.workflow', { batch: props.batch.id, action }));
const postBatch = () => router.post(route('dana.batches.post', props.batch.id));
const reverseBatch = () => {
    if (confirm('Reverse this posted batch? Inventory will be corrected via reversal transactions.')) {
        router.post(route('dana.batches.reverse', props.batch.id));
    }
};

const actionLabel = (action) =>
    ({
        submit: 'Submit',
        approve: 'Approve',
        start: 'Start production',
        complete: 'Mark completed',
        cancel: 'Cancel',
        return_to_draft: 'Return to draft',
        reopen: 'Reopen',
    })[action] ?? action;
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="batch.batch_number">
            <template #actions>
                <Link
                    v-if="canManage && ['draft', 'submitted'].includes(batch.status)"
                    :href="route('dana.batches.edit', batch.id)"
                    class="erp-btn-secondary"
                >
                    Edit
                </Link>
                <Link :href="route('dana.batches.print', batch.id)" class="erp-btn-secondary" target="_blank">Print</Link>
                <button v-if="canManage && batch.status === 'completed'" type="button" class="erp-btn-primary" @click="postBatch">
                    Post to inventory
                </button>
                <button
                    v-if="canManage && batch.status === 'posted'"
                    type="button"
                    class="erp-btn-secondary"
                    @click="reverseBatch"
                >
                    Reverse
                </button>
                <button
                    v-for="action in allowedActions"
                    :key="action"
                    type="button"
                    class="erp-btn-secondary"
                    @click="workflow(action)"
                >
                    {{ actionLabel(action) }}
                </button>
            </template>
        </ErpPageHeader>

        <ErpModuleIntro
            v-if="batch.status !== 'posted'"
            title="Next steps"
            help-text="Use the actions above to submit, approve, run production, and complete the batch. Posting is available once status is Completed."
        />

        <div class="erp-card mb-4 grid gap-2 p-4 text-sm sm:grid-cols-4">
            <div>Status: <ErpStatusBadge :status="batch.status" /></div>
            <div>Date: {{ batch.production_date }}</div>
            <div>Machine: {{ batch.machine?.name }}</div>
            <div>Hall: {{ batch.production_hall?.name }}</div>
        </div>

        <div class="mb-4 grid gap-4 lg:grid-cols-2">
            <ErpDataTable
                title="Material consumption"
                :columns="[
                    { key: 'material', label: 'Material' },
                    { key: 'type', label: 'Type' },
                    { key: 'qty', label: 'KG' },
                ]"
                :rows="
                    (batch.material_inputs ?? []).map((i) => ({
                        material: i.material?.name,
                        type: i.input_type,
                        qty: i.quantity_kg,
                    }))
                "
            />
            <ErpDataTable
                title="Operators"
                :columns="[
                    { key: 'name', label: 'Operator' },
                    { key: 'labor', label: 'Labor cost' },
                ]"
                :rows="
                    (batch.operators ?? []).map((o) => ({
                        name: o.operator_name,
                        labor: o.labor_cost,
                    }))
                "
            />
        </div>

        <div class="erp-card mb-4 p-4 text-sm">
            <h3 class="mb-2 font-semibold">Production quantities</h3>
            <p>Total input: {{ batch.total_input_kg }} KG · Output: {{ batch.output_kg }} KG</p>
            <p>
                Reusable: {{ batch.reusable_waste_kg }} · Scrap: {{ batch.scrap_waste_kg }} · Process loss:
                {{ batch.process_loss_kg }}
            </p>
        </div>

        <div v-if="previewCosts || batch.status === 'posted'" class="erp-card p-4 text-sm">
            <h3 class="mb-2 font-semibold">Costing</h3>
            <dl class="grid gap-1 sm:grid-cols-2">
                <div>Raw material: {{ batch.raw_material_cost ?? previewCosts?.raw_material_cost }}</div>
                <div>Color material: {{ batch.color_material_cost ?? previewCosts?.color_material_cost }}</div>
                <div>Direct labor: {{ batch.direct_labor_cost ?? previewCosts?.direct_labor_cost }}</div>
                <div>Electricity: {{ batch.electricity_cost }}</div>
                <div>Overhead: {{ batch.other_overhead_cost }}</div>
                <div class="font-semibold">
                    Total: {{ batch.total_batch_cost || previewCosts?.total_batch_cost }} · Per KG:
                    {{ batch.cost_per_kg || previewCosts?.cost_per_kg }}
                </div>
            </dl>
        </div>
    </ErpLayout>
</template>