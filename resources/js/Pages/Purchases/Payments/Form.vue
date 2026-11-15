<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    suppliers: Array,
    openInvoices: Array,
    paymentMethods: Object,
    preselectedSupplierId: Number,
});

const form = useForm({
    supplier_id: props.preselectedSupplierId ?? '',
    payment_date: new Date().toISOString().slice(0, 10),
    amount: 0,
    payment_method: 'cash',
    reference: '',
    notes: '',
    allocations: props.openInvoices.map((inv) => ({
        purchase_invoice_id: inv.id,
        amount: Math.max(0, Number(inv.grand_total) - Number(inv.amount_paid)),
        selected: false,
        label: inv.invoice_number,
        outstanding: Math.max(0, Number(inv.grand_total) - Number(inv.amount_paid)),
    })),
});

const totalAllocated = computed(() =>
    form.allocations.filter((a) => a.selected).reduce((sum, a) => sum + Number(a.amount || 0), 0),
);

const syncAmount = () => {
    form.amount = totalAllocated.value;
};

const submit = () => {
    syncAmount();
    const payload = {
        ...form.data(),
        allocations: form.allocations
            .filter((a) => a.selected && Number(a.amount) > 0)
            .map((a) => ({ purchase_invoice_id: a.purchase_invoice_id, amount: a.amount })),
    };
    form.transform(() => payload).post(route('purchases.payments.store'));
};
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Record supplier payment"
            help-text="Select open invoices, tick allocations, and ensure the payment amount matches the total allocated."
        />
        <form class="erp-card max-w-2xl space-y-4 p-6" @submit.prevent="submit">
            <select v-model="form.supplier_id" class="erp-input" required>
                <option value="">Supplier</option>
                <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
            <input v-model="form.payment_date" type="date" class="erp-input" required />
            <select v-model="form.payment_method" class="erp-input">
                <option v-for="(label, key) in paymentMethods" :key="key" :value="key">{{ label }}</option>
            </select>
            <div class="space-y-2">
                <div v-for="alloc in form.allocations" :key="alloc.purchase_invoice_id" class="flex items-center gap-2 text-sm">
                    <input v-model="alloc.selected" type="checkbox" @change="syncAmount" />
                    <span class="flex-1">{{ alloc.label }} ({{ alloc.outstanding }})</span>
                    <input v-model="alloc.amount" type="number" step="0.01" class="erp-input w-28" @input="syncAmount" />
                </div>
            </div>
            <p class="text-sm">Payment total: <strong>{{ totalAllocated }}</strong></p>
            <button type="submit" class="erp-btn-primary" :disabled="form.processing">Record payment</button>
        </form>
    </ErpLayout>
</template>