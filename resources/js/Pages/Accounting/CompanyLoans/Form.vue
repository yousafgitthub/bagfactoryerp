<script setup>
import ErpFormField from '@/Components/Erp/ErpFormField.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, directions: Object, treasuryAccounts: Array });

const form = useForm({
    direction: 'received',
    counterparty_name: '',
    principal: '',
    start_date: new Date().toISOString().slice(0, 10),
    maturity_date: '',
    interest_rate: 0,
    treasury_account_id: props.treasuryAccounts[0]?.id ?? '',
    notes: '',
});

const submit = () => form.post(route('accounting.company-loans.store'));
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="New company loan" />
        <form class="erp-card max-w-xl space-y-4 p-6" @submit.prevent="submit">
            <ErpFormField label="Direction"><select v-model="form.direction" class="erp-input"><option v-for="(l, k) in directions" :key="k" :value="k">{{ l }}</option></select></ErpFormField>
            <ErpFormField label="Counterparty" :error="form.errors.counterparty_name"><input v-model="form.counterparty_name" class="erp-input" required /></ErpFormField>
            <ErpFormField label="Principal" :error="form.errors.principal"><input v-model="form.principal" type="number" step="0.01" class="erp-input" required /></ErpFormField>
            <ErpFormField label="Treasury account"><select v-model="form.treasury_account_id" class="erp-input"><option v-for="t in treasuryAccounts" :key="t.id" :value="t.id">{{ t.name }}</option></select></ErpFormField>
            <ErpFormField label="Start date"><input v-model="form.start_date" type="date" class="erp-input" /></ErpFormField>
            <ErpFormField label="Maturity"><input v-model="form.maturity_date" type="date" class="erp-input" /></ErpFormField>
            <button type="submit" class="erp-btn-primary" :disabled="form.processing">Save & post</button>
        </form>
    </ErpLayout>
</template>