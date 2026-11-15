<script setup>
import ErpFormSection from '@/Components/Erp/ErpFormSection.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    record: Object,
    lineTypes: Object,
    paymentMethods: Object,
    canManage: Boolean,
});

const paymentForm = useForm({
    payment_date: new Date().toISOString().slice(0, 10),
    amount: '',
    payment_method: 'cash',
    reference: '',
    notes: '',
});

const lineForm = useForm({
    lines: [{ line_type: 'bonus', description: '', amount: '' }],
});

const submitPayment = () => paymentForm.post(route('hr.payroll.records.payments.store', props.record.id));

const submitLines = () => lineForm.put(route('hr.payroll.records.lines', props.record.id));

const recalculate = () => router.post(route('hr.payroll.records.recalculate', props.record.id));
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="record.employee?.name">
            <template #actions>
                <ErpStatusBadge :status="record.status" />
                <Link :href="route('hr.payroll.records.payslip', record.id)" class="erp-btn-secondary ml-2" target="_blank">Print slip</Link>
                <button
                    v-if="canManage && ['draft', 'reviewed'].includes(record.status)"
                    type="button"
                    class="erp-btn-secondary ml-2"
                    @click="recalculate"
                >
                    Recalculate
                </button>
            </template>
        </ErpPageHeader>

        <div class="grid gap-6 lg:grid-cols-2">
            <ErpFormSection title="Summary">
                <dl class="grid gap-2 text-sm">
                    <div class="flex justify-between"><dt class="text-erp-muted">Gross</dt><dd class="tabular-nums">{{ record.gross_amount }}</dd></div>
                    <div class="flex justify-between"><dt class="text-erp-muted">Deductions</dt><dd class="tabular-nums">{{ record.deductions_total }}</dd></div>
                    <div class="flex justify-between"><dt class="text-erp-muted">Net payable</dt><dd class="tabular-nums font-medium">{{ record.net_payable }}</dd></div>
                    <div class="flex justify-between"><dt class="text-erp-muted">Paid</dt><dd class="tabular-nums">{{ record.amount_paid }}</dd></div>
                    <div class="flex justify-between"><dt class="text-erp-muted">Outstanding</dt><dd class="tabular-nums">{{ record.outstanding_amount }}</dd></div>
                </dl>
            </ErpFormSection>

            <ErpFormSection title="Lines">
                <ul class="space-y-1 text-sm">
                    <li v-for="line in record.lines" :key="line.id" class="flex justify-between gap-4">
                        <span>{{ lineTypes[line.line_type] ?? line.line_type }} — {{ line.description }}</span>
                        <span class="tabular-nums" :class="line.amount < 0 ? 'text-red-600' : ''">{{ line.amount }}</span>
                    </li>
                </ul>
            </ErpFormSection>
        </div>

        <form
            v-if="canManage && ['draft', 'reviewed'].includes(record.status)"
            class="erp-card mt-6 space-y-3 p-4"
            @submit.prevent="submitLines"
        >
            <h3 class="font-medium">Bonuses / allowances / other deductions</h3>
            <div v-for="(line, idx) in lineForm.lines" :key="idx" class="flex flex-wrap gap-2">
                <select v-model="line.line_type" class="erp-input">
                    <option value="bonus">Bonus</option>
                    <option value="allowance">Allowance</option>
                    <option value="other_deduction">Other deduction</option>
                </select>
                <input v-model="line.description" type="text" class="erp-input flex-1" placeholder="Description" />
                <input v-model="line.amount" type="number" step="0.01" min="0" class="erp-input w-28" placeholder="Amount" />
            </div>
            <button type="button" class="erp-btn-secondary text-sm" @click="lineForm.lines.push({ line_type: 'bonus', description: '', amount: '' })">
                + Line
            </button>
            <button type="submit" class="erp-btn-primary block" :disabled="lineForm.processing">Save manual lines</button>
        </form>

        <form
            v-if="canManage && ['approved', 'partially_paid'].includes(record.status) && record.outstanding_amount > 0"
            class="erp-card mt-6 max-w-lg space-y-3 p-4"
            @submit.prevent="submitPayment"
        >
            <h3 class="font-medium">Record salary payment</h3>
            <input v-model="paymentForm.payment_date" type="date" class="erp-input w-full" required />
            <input v-model="paymentForm.amount" type="number" step="0.01" min="0.01" class="erp-input w-full" placeholder="Amount" required />
            <select v-model="paymentForm.payment_method" class="erp-input w-full">
                <option v-for="(label, key) in paymentMethods" :key="key" :value="key">{{ label }}</option>
            </select>
            <input v-model="paymentForm.reference" type="text" class="erp-input w-full" placeholder="Reference" />
            <button type="submit" class="erp-btn-primary" :disabled="paymentForm.processing">Post payment</button>
        </form>

        <ErpFormSection v-if="record.payments?.length" title="Payment history" class="mt-6">
            <ul class="space-y-2 text-sm">
                <li v-for="p in record.payments" :key="p.id" class="flex justify-between">
                    <span>{{ p.payment_date }} — {{ paymentMethods[p.payment_method] ?? p.payment_method }}</span>
                    <span class="tabular-nums">{{ p.amount }}</span>
                </li>
            </ul>
        </ErpFormSection>
    </ErpLayout>
</template>