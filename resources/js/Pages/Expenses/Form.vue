<script setup>
import ErpFormField from '@/Components/Erp/ErpFormField.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    categories: Array,
    treasuryAccounts: Array,
    paymentStatuses: Object,
    paymentMethods: Object,
});

const form = useForm({
    expense_category_id: '',
    expense_date: new Date().toISOString().slice(0, 10),
    amount: '',
    payment_status: 'paid',
    payment_method: 'cash',
    treasury_account_id: props.treasuryAccounts[0]?.id ?? '',
    description: '',
});

const submit = () => form.post(route('expenses.entries.store'));
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="New expense" />
        <form class="erp-card max-w-xl space-y-4 p-6" @submit.prevent="submit">
            <ErpFormField label="Category" :error="form.errors.expense_category_id">
                <select v-model="form.expense_category_id" class="erp-input" required>
                    <option value="" disabled>Select…</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </ErpFormField>
            <ErpFormField label="Date" :error="form.errors.expense_date">
                <input v-model="form.expense_date" type="date" class="erp-input" required />
            </ErpFormField>
            <ErpFormField label="Amount" :error="form.errors.amount">
                <input v-model="form.amount" type="number" step="0.01" min="0.01" class="erp-input" required />
            </ErpFormField>
            <ErpFormField label="Payment status" :error="form.errors.payment_status">
                <select v-model="form.payment_status" class="erp-input">
                    <option v-for="(label, key) in paymentStatuses" :key="key" :value="key">{{ label }}</option>
                </select>
            </ErpFormField>
            <template v-if="form.payment_status === 'paid'">
                <ErpFormField label="Payment method" :error="form.errors.payment_method">
                    <select v-model="form.payment_method" class="erp-input">
                        <option v-for="(label, key) in paymentMethods" :key="key" :value="key">{{ label }}</option>
                    </select>
                </ErpFormField>
                <ErpFormField label="Cash / bank account" :error="form.errors.treasury_account_id">
                    <select v-model="form.treasury_account_id" class="erp-input">
                        <option v-for="t in treasuryAccounts" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>
                </ErpFormField>
            </template>
            <ErpFormField label="Description" :error="form.errors.description">
                <textarea v-model="form.description" class="erp-input" rows="2" />
            </ErpFormField>
            <button type="submit" class="erp-btn-primary" :disabled="form.processing">Save & post</button>
        </form>
    </ErpLayout>
</template>