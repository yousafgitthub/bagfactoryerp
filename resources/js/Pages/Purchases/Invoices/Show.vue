<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, invoice: Object, canManage: Boolean });

const postInvoice = () => {
    if (confirm('Post invoice to supplier ledger? (No stock change)')) {
        router.post(route('purchases.invoices.post', props.invoice.id));
    }
};
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="invoice.invoice_number">
            <template #actions>
                <ErpStatusBadge :status="invoice.status" class="mr-2" />
                <button v-if="canManage && invoice.status === 'draft'" type="button" class="erp-btn-primary" @click="postInvoice">Post invoice</button>
            </template>
        </ErpPageHeader>
        <div class="mb-4 text-sm">Grand total: <strong>{{ invoice.grand_total }}</strong> · Paid: {{ invoice.amount_paid }}</div>
        <ErpDataTable
            :columns="[
                { key: 'material', label: 'Material' },
                { key: 'qty', label: 'Qty' },
                { key: 'total', label: 'Line total' },
            ]"
            :rows="
                (invoice.lines || []).map((l) => ({
                    material: l.material?.name,
                    qty: l.quantity,
                    total: l.line_total,
                }))
            "
        />
    </ErpLayout>
</template>