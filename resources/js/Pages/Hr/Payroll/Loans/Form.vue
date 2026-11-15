<script setup>
import ErpFormSection from '@/Components/Erp/ErpFormSection.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    lookups: Object,
});

const form = useForm({
    employee_id: '',
    loan_date: new Date().toISOString().slice(0, 10),
    principal: '',
    installment_amount: '',
    installment_count: '',
    notes: '',
});

const submit = () => form.post(route('hr.payroll.loans.store'));
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Record employee loan" />

        <form class="erp-card max-w-xl space-y-4 p-6" @submit.prevent="submit">
            <ErpFormSection title="Loan">
                <select v-model="form.employee_id" class="erp-input w-full" required>
                    <option value="" disabled>Select employee</option>
                    <option v-for="e in lookups.employees" :key="e.id" :value="e.id">{{ e.name }} ({{ e.employee_code }})</option>
                </select>
                <input v-model="form.loan_date" type="date" class="erp-input w-full" required />
                <input v-model="form.principal" type="number" step="0.01" min="0.01" class="erp-input w-full" placeholder="Principal" required />
                <input v-model="form.installment_amount" type="number" step="0.01" min="0.01" class="erp-input w-full" placeholder="Installment amount" required />
                <input v-model="form.installment_count" type="number" min="1" class="erp-input w-full" placeholder="Installment count (optional)" />
                <textarea v-model="form.notes" class="erp-input w-full" rows="2" />
            </ErpFormSection>
            <button type="submit" class="erp-btn-primary" :disabled="form.processing">Save</button>
        </form>
    </ErpLayout>
</template>