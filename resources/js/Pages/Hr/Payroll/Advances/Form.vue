<script setup>
import ErpFormSection from '@/Components/Erp/ErpFormSection.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    lookups: Object,
});

const form = useForm({
    employee_id: '',
    advance_date: new Date().toISOString().slice(0, 10),
    amount: '',
    reason: '',
    per_payroll_deduction: '',
    notes: '',
});

const submit = () => form.post(route('hr.payroll.advances.store'));
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Record salary advance" />

        <form class="erp-card max-w-xl space-y-4 p-6" @submit.prevent="submit">
            <ErpFormSection title="Advance">
                <select v-model="form.employee_id" class="erp-input w-full" required>
                    <option value="" disabled>Select employee</option>
                    <option v-for="e in lookups.employees" :key="e.id" :value="e.id">{{ e.name }} ({{ e.employee_code }})</option>
                </select>
                <input v-model="form.advance_date" type="date" class="erp-input w-full" required />
                <input v-model="form.amount" type="number" step="0.01" min="0.01" class="erp-input w-full" placeholder="Amount" required />
                <input v-model="form.reason" type="text" class="erp-input w-full" placeholder="Reason" />
                <input v-model="form.per_payroll_deduction" type="number" step="0.01" min="0" class="erp-input w-full" placeholder="Per payroll deduction (optional)" />
                <textarea v-model="form.notes" class="erp-input w-full" rows="2" placeholder="Notes" />
            </ErpFormSection>
            <button type="submit" class="erp-btn-primary" :disabled="form.processing">Save</button>
        </form>
    </ErpLayout>
</template>