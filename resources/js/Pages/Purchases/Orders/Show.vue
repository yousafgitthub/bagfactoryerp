<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, order: Object, canManage: Boolean });

const grnForm = useForm({
    receipt_date: new Date().toISOString().slice(0, 10),
    notes: '',
    lines: (props.order.lines || [])
        .filter((l) => Number(l.ordered_qty) > Number(l.received_qty))
        .map((l) => ({
            purchase_order_line_id: l.id,
            received_qty: Math.max(0, Number(l.ordered_qty) - Number(l.received_qty)),
        })),
});

const invoiceForm = useForm({
    invoice_date: new Date().toISOString().slice(0, 10),
    due_date: '',
    supplier_invoice_number: '',
    notes: '',
    lines: (props.order.lines || [])
        .filter((l) => Number(l.received_qty) > Number(l.invoiced_qty))
        .map((l) => ({
            purchase_order_line_id: l.id,
            quantity: Math.max(0, Number(l.received_qty) - Number(l.invoiced_qty)),
        })),
});

const confirmPo = () => router.post(route('purchases.orders.confirm', props.order.id));
const submitGrn = () => grnForm.post(route('purchases.orders.goods-receipts.store', props.order.id));
const submitInvoice = () => invoiceForm.post(route('purchases.orders.invoices.store', props.order.id));
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="order.order_number">
            <template #actions>
                <ErpStatusBadge :status="order.status" class="mr-2" />
                <Link v-if="canManage && order.status === 'draft'" :href="route('purchases.orders.edit', order.id)" class="erp-btn-secondary">Edit</Link>
                <button v-if="canManage && order.status === 'draft'" type="button" class="erp-btn-primary" @click="confirmPo">Confirm PO</button>
            </template>
        </ErpPageHeader>
        <ErpDataTable
            class="mb-6"
            title="Lines"
            :columns="[
                { key: 'material', label: 'Material' },
                { key: 'ordered', label: 'Ordered' },
                { key: 'received', label: 'Received' },
                { key: 'invoiced', label: 'Invoiced' },
                { key: 'price', label: 'Unit price' },
            ]"
            :rows="
                (order.lines || []).map((l) => ({
                    material: l.material?.name,
                    ordered: l.ordered_qty,
                    received: l.received_qty,
                    invoiced: l.invoiced_qty,
                    price: l.unit_price,
                }))
            "
        />
        <form v-if="canManage && ['confirmed', 'partially_received', 'received'].includes(order.status)" class="erp-card mb-6 space-y-3 p-4" @submit.prevent="submitGrn">
            <h3 class="font-medium">Partial goods receipt</h3>
            <input v-model="grnForm.receipt_date" type="date" class="erp-input max-w-xs" required />
            <div v-for="(line, i) in grnForm.lines" :key="i" class="flex gap-2 text-sm">
                <span class="w-32">Line {{ line.purchase_order_line_id }}</span>
                <input v-model="line.received_qty" type="number" step="0.0001" class="erp-input max-w-[120px]" />
            </div>
            <button type="submit" class="erp-btn-primary" :disabled="grnForm.processing">Create GRN draft</button>
        </form>
        <form
            v-if="canManage && (order.lines || []).some((l) => Number(l.received_qty) > Number(l.invoiced_qty))"
            class="erp-card space-y-3 p-4"
            @submit.prevent="submitInvoice"
        >
            <h3 class="font-medium">Invoice matching (received qty)</h3>
            <input v-model="invoiceForm.invoice_date" type="date" class="erp-input max-w-xs" required />
            <input v-model="invoiceForm.supplier_invoice_number" class="erp-input max-w-xs" placeholder="Supplier bill #" />
            <div v-for="(line, i) in invoiceForm.lines" :key="i" class="flex gap-2 text-sm">
                <span class="w-32">Line {{ line.purchase_order_line_id }}</span>
                <input v-model="line.quantity" type="number" step="0.0001" class="erp-input max-w-[120px]" />
            </div>
            <button type="submit" class="erp-btn-secondary" :disabled="invoiceForm.processing">Create invoice draft</button>
        </form>
    </ErpLayout>
</template>