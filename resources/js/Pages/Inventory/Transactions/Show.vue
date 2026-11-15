<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    transaction: Object,
    transactionTypes: Object,
    canManage: Boolean,
});

const postTx = () => {
    router.post(route('inventory.transactions.post', props.transaction.id));
};

const reverseTx = () => {
    if (confirm('Post a reversal for this transaction?')) {
        router.post(route('inventory.transactions.reverse', props.transaction.id));
    }
};
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="transaction.transaction_number">
            <template #actions>
                <button
                    v-if="canManage && transaction.status === 'draft'"
                    type="button"
                    class="erp-btn-primary"
                    @click="postTx"
                >
                    Post
                </button>
                <button
                    v-if="canManage && transaction.status === 'posted' && transaction.type !== 'reversal'"
                    type="button"
                    class="erp-btn-secondary"
                    @click="reverseTx"
                >
                    Reverse
                </button>
            </template>
        </ErpPageHeader>

        <div class="erp-card mb-4 grid gap-2 p-4 text-sm sm:grid-cols-3">
            <div>Type: {{ transactionTypes[transaction.type] ?? transaction.type }}</div>
            <div>Date: {{ transaction.transaction_date }}</div>
            <div>Status: <ErpStatusBadge :status="transaction.status" /></div>
        </div>

        <ErpDataTable
            v-if="transaction.items?.length"
            title="Lines"
            class="mb-4"
            :columns="[
                { key: 'material', label: 'Material' },
                { key: 'qty', label: 'Qty' },
                { key: 'cost', label: 'Unit cost' },
            ]"
            :rows="
                transaction.items.map((i) => ({
                    material: i.material?.name,
                    qty: i.quantity,
                    cost: i.unit_cost,
                }))
            "
        />

        <ErpDataTable
            v-if="transaction.stock_transfer?.items?.length"
            title="Transfer lines"
            class="mb-4"
            :columns="[
                { key: 'material', label: 'Material' },
                { key: 'qty', label: 'Qty' },
            ]"
            :rows="
                transaction.stock_transfer.items.map((i) => ({
                    material: i.material?.name,
                    qty: i.quantity,
                }))
            "
        />

        <ErpDataTable
            v-if="transaction.ledger_entries?.length"
            title="Posted ledger"
            :columns="[
                { key: 'movement', label: 'Type' },
                { key: 'qty', label: 'Qty' },
                { key: 'cost', label: 'Unit cost' },
            ]"
            :rows="
                transaction.ledger_entries.map((e) => ({
                    movement: e.movement_type,
                    qty: e.quantity,
                    cost: e.unit_cost,
                }))
            "
        />
    </ErpLayout>
</template>