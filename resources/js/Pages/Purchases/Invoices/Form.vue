<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, purchaseOrder: Object, suppliers: Array });

const form = useForm({
    supplier_id: props.purchaseOrder?.supplier_id ?? '',
    purchase_order_id: props.purchaseOrder?.id ?? null,
    supplier_invoice_number: '',
    invoice_date: new Date().toISOString().slice(0, 10),
    due_date: '',
    notes: '',
    lines: (props.purchaseOrder?.lines || []).map((l) => ({
        purchase_order_line_id: l.id,
        material_id: l.material_id,
        material_variant_id: l.material_variant_id,
        quantity: Math.max(0, Number(l.received_qty) - Number(l.invoiced_qty)),
        unit_price: l.unit_price,
        discount_amount: l.discount_amount,
        tax_rate: l.tax_rate,
    })),
});

const submit = () => form.post(route('purchases.invoices.store'));
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="pageTitle" />
        <form class="erp-card max-w-3xl space-y-4 p-6" @submit.prevent="submit">
            <select v-model="form.supplier_id" class="erp-input" required>
                <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
            <input v-model="form.invoice_date" type="date" class="erp-input" required />
            <button type="submit" class="erp-btn-primary">Save draft</button>
        </form>
    </ErpLayout>
</template>