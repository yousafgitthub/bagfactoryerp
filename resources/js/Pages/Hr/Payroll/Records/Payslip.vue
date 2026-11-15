<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    pageTitle: String,
    record: Object,
    lineTypes: Object,
});
</script>

<template>
    <Head :title="pageTitle" />

    <div class="mx-auto max-w-2xl bg-white p-8 text-slate-900 print:p-4">
        <div class="border-b pb-4">
            <h1 class="text-xl font-bold">Salary slip</h1>
            <p class="text-sm text-slate-600">{{ record.run?.period_label }} · {{ record.period_start }} — {{ record.period_end }}</p>
        </div>

        <dl class="mt-4 grid grid-cols-2 gap-2 text-sm">
            <dt>Employee</dt>
            <dd class="font-medium">{{ record.employee?.name }} ({{ record.employee?.employee_code }})</dd>
            <dt>Department</dt>
            <dd>{{ record.employee?.department?.name ?? '—' }}</dd>
            <dt>Pay type</dt>
            <dd>{{ record.salary_type }}</dd>
        </dl>

        <table class="mt-6 w-full text-sm">
            <thead>
                <tr class="border-b text-left text-slate-600">
                    <th class="py-2">Description</th>
                    <th class="py-2 text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="line in record.lines" :key="line.id" class="border-b border-slate-100">
                    <td class="py-2">{{ line.description ?? lineTypes[line.line_type] }}</td>
                    <td class="py-2 text-right tabular-nums">{{ line.amount }}</td>
                </tr>
            </tbody>
        </table>

        <dl class="mt-6 space-y-1 text-sm">
            <div class="flex justify-between font-semibold">
                <dt>Net payable</dt>
                <dd class="tabular-nums">{{ record.net_payable }}</dd>
            </div>
            <div class="flex justify-between">
                <dt>Paid to date</dt>
                <dd class="tabular-nums">{{ record.amount_paid }}</dd>
            </div>
            <div class="flex justify-between">
                <dt>Outstanding</dt>
                <dd class="tabular-nums">{{ record.outstanding_amount }}</dd>
            </div>
        </dl>

        <button type="button" class="mt-8 rounded border px-4 py-2 text-sm print:hidden" @click="window.print()">Print</button>
    </div>
</template>