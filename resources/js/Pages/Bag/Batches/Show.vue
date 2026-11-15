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
    allowedActions: Array,
    postingPreview: Object,
    canManage: Boolean,
});

const workflow = (action) => router.post(route('bag.batches.workflow', { batch: props.batch.id, action }));
const postBatch = () => router.post(route('bag.batches.post', props.batch.id));
const reverseBatch = () => {
    if (confirm('Reverse posted batch? Inventory will be corrected.')) {
        router.post(route('bag.batches.reverse', props.batch.id));
    }
};

const actionLabel = (action) =>
    ({
        submit: 'Submit',
        approve: 'Approve',
        start: 'Start',
        complete: 'Complete',
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
                <Link v-if="canManage && ['draft', 'submitted'].includes(batch.status)" :href="route('bag.batches.edit', batch.id)" class="erp-btn-secondary">Edit</Link>
                <button v-if="canManage && batch.status === 'completed'" type="button" class="erp-btn-primary" @click="postBatch">Post to inventory</button>
                <button v-if="canManage && batch.status === 'posted'" type="button" class="erp-btn-secondary" @click="reverseBatch">Reverse</button>
                <button v-for="action in allowedActions" :key="action" type="button" class="erp-btn-secondary" @click="workflow(action)">{{ actionLabel(action) }}</button>
            </template>
        </ErpPageHeader>

        <ErpModuleIntro
            v-if="batch.status !== 'posted'"
            title="Next steps"
            help-text="Advance the batch through shop-floor steps, then post to consume Dana or materials and receive finished bags."
        />

        <div class="erp-card mb-4 grid gap-2 p-4 text-sm sm:grid-cols-4">
            <div>Status: <ErpStatusBadge :status="batch.status" /></div>
            <div>Good: {{ batch.good_quantity }} / Rejected: {{ batch.rejected_quantity }}</div>
            <div>Dana: {{ batch.dana_consumed_kg }} KG</div>
            <div>Weight/bag: {{ batch.actual_weight_per_good_bag_g ?? '—' }} g</div>
        </div>

        <div v-if="postingPreview" class="erp-card mb-4 p-4 text-sm">
            <h3 class="mb-2 font-semibold">Pre-post consumption preview</h3>
            <p>Est. batch cost: {{ postingPreview.costs?.total_batch_cost }} · Per good bag: {{ postingPreview.costs?.cost_per_good_bag }}</p>
            <p class="text-erp-muted">
                Mass balance delta: {{ postingPreview.mass_balance?.delta_kg }} KG (tolerance enforced at post)
            </p>
            <ul v-if="postingPreview.stock_checks?.length" class="mt-2 list-disc pl-5">
                <li v-for="(c, i) in postingPreview.stock_checks" :key="i" :class="c.sufficient ? '' : 'text-red-600'">
                    {{ c.material }}: need {{ c.required }}, available {{ c.available }}
                </li>
            </ul>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <ErpDataTable
                title="Material lines"
                :columns="[
                    { key: 'material', label: 'Material' },
                    { key: 'type', label: 'Type' },
                    { key: 'qty', label: 'Qty' },
                ]"
                :rows="
                    (batch.material_consumptions ?? []).map((i) => ({
                        material: i.material?.name,
                        type: i.consumption_type,
                        qty: i.quantity,
                    }))
                "
            />
            <ErpDataTable
                title="Operators"
                :columns="[
                    { key: 'name', label: 'Name' },
                    { key: 'cost', label: 'Labor' },
                ]"
                :rows="
                    (batch.operators ?? []).map((o) => ({
                        name: o.operator_name,
                        cost: o.labor_cost,
                    }))
                "
            />
        </div>
    </ErpLayout>
</template>