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
        <ErpPageHeader
            title="Record employee loan"
            help-text="Loans are repaid through payroll deductions. Installment amount and count set the recovery schedule — payroll runs apply them automatically; you can adjust notes only here."
        />

        <form class="erp-card max-w-xl space-y-4 p-6" @submit.prevent="submit">
            <ErpFormSection title="Loan details">
                <ErpFormField label="Employee" name="employee_id" help="Staff member who received the loan disbursement." required :error="form.errors.employee_id">
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
                <ErpFormField label="Loan date" name="loan_date" help="Disbursement date for audit and payroll recovery." required :error="form.errors.loan_date">
                    <template #default="{ describedBy }">
                        <input
                            :id="'loan_date'"
                            v-model="form.loan_date"
                            type="date"
                            class="erp-input w-full"
                            :aria-describedby="describedBy"
                            required
                        />
                    </template>
                </ErpFormField>
                <ErpFormField label="Principal" name="principal" help="Total loan amount before interest (if any is tracked outside ERP)." required :error="form.errors.principal">
                    <template #default="{ describedBy }">
                        <input
                            :id="'principal'"
                            v-model="form.principal"
                            type="number"
                            step="0.01"
                            min="0.01"
                            class="erp-input w-full"
                            :aria-describedby="describedBy"
                            required
                        />
                    </template>
                </ErpFormField>
                <ErpFormField
                    label="Installment amount"
                    name="installment_amount"
                    help="Amount deducted from each payroll until the balance is cleared."
                    required
                    :error="form.errors.installment_amount"
                >
                    <template #default="{ describedBy }">
                        <input
                            :id="'installment_amount'"
                            v-model="form.installment_amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            class="erp-input w-full"
                            :aria-describedby="describedBy"
                            required
                        />
                    </template>
                </ErpFormField>
                <ErpFormField
                    label="Installment count"
                    name="installment_count"
                    help="Optional planned number of repayments. Leave empty if recovery is open-ended."
                    :error="form.errors.installment_count"
                >
                    <template #default="{ describedBy }">
                        <input
                            :id="'installment_count'"
                            v-model="form.installment_count"
                            type="number"
                            min="1"
                            class="erp-input w-full"
                            :aria-describedby="describedBy"
                        />
                    </template>
                </ErpFormField>
                <ErpFormField label="Notes" name="notes" help="Internal loan reference for HR." :error="form.errors.notes">
                    <template #default="{ describedBy }">
                        <textarea :id="'notes'" v-model="form.notes" class="erp-input w-full" rows="2" :aria-describedby="describedBy" />
                    </template>
                </ErpFormField>
            </ErpFormSection>
            <button type="submit" class="erp-btn-primary" :disabled="form.processing">Save</button>
        </form>
    </ErpLayout>
</template>