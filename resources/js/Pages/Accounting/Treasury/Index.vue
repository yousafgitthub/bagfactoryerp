<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpFormField from '@/Components/Erp/ErpFormField.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    accounts: Array,
    movements: Array,
    canManage: Boolean,
});

const form = useForm({
    from_treasury_account_id: props.accounts[0]?.id ?? '',
    to_treasury_account_id: props.accounts[1]?.id ?? '',
    movement_date: new Date().toISOString().slice(0, 10),
    amount: '',
    notes: '',
});

const submit = () => form.post(route('accounting.treasury.transfer'));
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Cash & bank" />
        <ErpDataTable
            class="mb-6"
            :columns="[
                { key: 'code', label: 'Account' },
                { key: 'type', label: 'Type' },
                { key: 'balance', label: 'Balance' },
            ]"
            :rows="
                accounts.map((a) => ({
                    code: `${a.code} ${a.name}`,
                    type: a.account_type,
                    balance: a.current_balance,
                }))
            "
        />
        <form v-if="canManage" class="erp-card mb-6 max-w-xl space-y-3 p-6" @submit.prevent="submit">
            <h3 class="font-semibold">Transfer between accounts</h3>
            <ErpFormField label="From"><select v-model="form.from_treasury_account_id" class="erp-input"><option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option></select></ErpFormField>
            <ErpFormField label="To"><select v-model="form.to_treasury_account_id" class="erp-input"><option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option></select></ErpFormField>
            <ErpFormField label="Date"><input v-model="form.movement_date" type="date" class="erp-input" /></ErpFormField>
            <ErpFormField label="Amount" :error="form.errors.amount"><input v-model="form.amount" type="number" step="0.01" class="erp-input" /></ErpFormField>
            <button type="submit" class="erp-btn-primary" :disabled="form.processing">Post transfer</button>
        </form>
        <ErpDataTable
            :columns="[
                { key: 'number', label: 'Ref' },
                { key: 'account', label: 'Account' },
                { key: 'type', label: 'Type' },
                { key: 'date', label: 'Date' },
                { key: 'amount', label: 'Amount' },
            ]"
            :rows="
                movements.map((m) => ({
                    number: m.movement_number,
                    account: m.treasury_account?.name,
                    type: m.movement_type,
                    date: m.movement_date,
                    amount: m.amount,
                }))
            "
        />
    </ErpLayout>
</template>