<script setup>
import ErpFormField from '@/Components/Erp/ErpFormField.vue';
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
        <ErpPageHeader
            title="Record salary advance"
            help-text="Advances reduce net pay on future payroll runs. Set an optional fixed deduction per run, or leave blank to deduct the full balance when payslips are calculated."
        />

        <form class="erp-card max-w-xl space-y-4 p-6" @submit.prevent="submit">
            <ErpFormSection title="Advance details">
                <ErpFormField label="Employee" name="employee_id" help="Choose the staff member who received the advance." required :error="form.errors.employee_id">
                    <template #default="{ describedBy }">
                        <select
                            :id="'employee_id'"
                            v-model="form.employee_id"
                            class="erp-input w-full"
                            :aria-describedby="describedBy"
                            required
                        >
                            <option value="" disabled>Select employee</option>
                            <option v-for="e in lookups.employees" :key="e.id" :value="e.id">
                                {{ e.name }} ({{ e.employee_code }})
                            </option>
                        </select>
                    </template>
                </ErpFormField>
                <ErpFormField label="Advance date" name="advance_date" help="The date cash or bank was paid out." required :error="form.errors.advance_date">
                    <template #default="{ describedBy }">
                        <input
                            :id="'advance_date'"
                            v-model="form.advance_date"
                            type="date"
                            class="erp-input w-full"
                            :aria-describedby="describedBy"
                            required
                        />
                    </template>
                </ErpFormField>
                <ErpFormField label="Amount" name="amount" help="Enter the gross advance in your payroll currency." required :error="form.errors.amount">
                    <template #default="{ describedBy }">
                        <input
                            :id="'amount'"
                            v-model="form.amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            class="erp-input w-full"
                            :aria-describedby="describedBy"
                            required
                        />
                    </template>
                </ErpFormField>
                <ErpFormField label="Reason" name="reason" help="Short note for payroll review (for example emergency or festival advance)." :error="form.errors.reason">
                    <template #default="{ describedBy }">
                        <input :id="'reason'" v-model="form.reason" type="text" class="erp-input w-full" :aria-describedby="describedBy" />
                    </template>
                </ErpFormField>
                <ErpFormField
                    label="Per payroll deduction"
                    name="per_payroll_deduction"
                    help="Optional cap on how much is recovered on each payroll run. Leave empty to recover the remaining balance according to payroll rules."
                    :error="form.errors.per_payroll_deduction"
                >
                    <template #default="{ describedBy }">
                        <input
                            :id="'per_payroll_deduction'"
                            v-model="form.per_payroll_deduction"
                            type="number"
                            step="0.01"
                            min="0"
                            class="erp-input w-full"
                            :aria-describedby="describedBy"
                        />
                    </template>
                </ErpFormField>
                <ErpFormField label="Notes" name="notes" help="Internal remarks for HR — not printed on payslips." :error="form.errors.notes">
                    <template #default="{ describedBy }">
                        <textarea :id="'notes'" v-model="form.notes" class="erp-input w-full" rows="2" :aria-describedby="describedBy" />
                    </template>
                </ErpFormField>
            </ErpFormSection>
            <button type="submit" class="erp-btn-primary" :disabled="form.processing">Save</button>
        </form>
    </ErpLayout>
</template>