<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, receipt: Object, canManage: Boolean });

const postGrn = () => {
    if (confirm('Post goods receipt? Stock will increase now.')) {
        router.post(route('purchases.goods-receipts.post', props.receipt.id));
    }
};
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            :title="receipt.receipt_number"
            help-text="While draft you can edit quantities. Posting increases stock and links an inventory movement — this is when raw material enters the factory."
        >
            <template #actions>
                <ErpStatusBadge :status="receipt.status" class="mr-2" />
                <button v-if="canManage && receipt.status === 'draft'" type="button" class="erp-btn-primary" @click="postGrn">Post GRN</button>
            </template>
        </ErpPageHeader>
        <div class="mb-4 grid gap-2 text-sm sm:grid-cols-2">
            <div>Supplier: <strong>{{ receipt.supplier?.name ?? '—' }}</strong></div>
            <div>Receipt date: <strong>{{ receipt.receipt_date ?? '—' }}</strong></div>
        </div>
        <p v-if="receipt.inventory_transaction" class="mb-4 text-sm">
            Inventory:
            <Link v-if="receipt.inventory_transaction" :href="route('inventory.transactions.show', receipt.inventory_transaction.id)" class="erp-link">
                {{ receipt.inventory_transaction.transaction_number }}
            </Link>
        </p>
        <h3 class="mb-2 text-sm font-semibold text-slate-800 dark:text-slate-100">Received lines</h3>
        <ErpDataTable
            :columns="[
                { key: 'material', label: 'Material' },
                { key: 'qty', label: 'Received' },
                { key: 'cost', label: 'Unit cost' },
            ]"
            :rows="
                (receipt.lines || []).map((l) => ({
                    material: l.material?.name,
                    qty: l.received_qty,
                    cost: l.unit_cost,
                }))
            "
        />
    </ErpLayout>
</template>