<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    customers: Array,
    openInvoices: Array,
    paymentMethods: Object,
    prefillCustomerId: Number,
});

const form = useForm({
    customer_id: props.prefillCustomerId ?? '',
    receipt_date: new Date().toISOString().slice(0, 10),
    amount: 0,
    payment_method: 'cash',
    reference: '',
    notes: '',
    allocations: [{ sales_invoice_id: '', amount: 0 }],
});

const allocTotal = computed(() => form.allocations.reduce((s, a) => s + Number(a.amount || 0), 0));

const submit = () => {
    form.amount = allocTotal.value;
    form.post(route('sales.receipts.store'));
};
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Record receipt" />
        <form class="erp-card max-w-2xl space-y-4 p-6" @submit.prevent="submit">
            <select v-model="form.customer_id" class="erp-input w-full" required>
                <option value="">Customer</option>
                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <input v-model="form.receipt_date" type="date" class="erp-input w-full" required />
            <select v-model="form.payment_method" class="erp-input w-full">
                <option v-for="(label, key) in paymentMethods" :key="key" :value="key">{{ label }}</option>
            </select>
            <div v-for="(row, i) in form.allocations" :key="i" class="grid gap-2 sm:grid-cols-2">
                <select v-model="row.sales_invoice_id" class="erp-input" required>
                    <option value="">Invoice</option>
                    <option v-for="inv in openInvoices" :key="inv.id" :value="inv.id">{{ inv.invoice_number }} ({{ inv.grand_total - inv.amount_paid }})</option>
                </select>
                <input v-model="row.amount" type="number" step="0.01" class="erp-input" required />
            </div>
            <p class="text-sm">Total: {{ allocTotal }}</p>
            <button type="submit" class="erp-btn-primary">Save</button>
        </form>
    </ErpLayout>
</template>