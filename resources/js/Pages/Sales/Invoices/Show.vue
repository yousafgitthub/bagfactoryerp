<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, invoice: Object, canManage: Boolean, printUrl: String });

const postInvoice = () => {
    if (confirm('Post invoice? This deducts finished stock and records COGS at WAC.')) {
        router.post(route('sales.invoices.post', props.invoice.id));
    }
};
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="invoice.invoice_number">
            <template #actions>
                <Link :href="printUrl" class="erp-btn-secondary mr-2" target="_blank" rel="noopener">Print Invoice</Link>
                <ErpStatusBadge :status="invoice.status" class="mr-2" />
                <button v-if="canManage && invoice.status === 'draft'" type="button" class="erp-btn-primary" @click="postInvoice">Post</button>
            </template>
        </ErpPageHeader>
        <div class="mb-4 grid gap-2 text-sm sm:grid-cols-4">
            <div>Revenue: <strong>{{ invoice.grand_total }}</strong></div>
            <div>COGS: <strong>{{ invoice.cogs_total }}</strong></div>
            <div>Tax: {{ invoice.tax_total }}</div>
            <div>Paid: {{ invoice.amount_paid }}</div>
        </div>
        <ErpDataTable
            :columns="[
                { key: 'item', label: 'Item' },
                { key: 'qty', label: 'Qty' },
                { key: 'total', label: 'Line total' },
            ]"
            :rows="
                (invoice.lines || []).map((l) => ({
                    item: l.material?.name,
                    qty: l.quantity,
                    total: l.line_total,
                }))
            "
        />
    </ErpLayout>
</template>