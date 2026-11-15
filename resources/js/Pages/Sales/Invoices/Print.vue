<script setup>
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';

defineProps({ pageTitle: String, invoice: Object, company: Object });

onMounted(() => {
    setTimeout(() => window.print(), 300);
});
</script>

<template>
    <Head :title="pageTitle" />
    <div class="mx-auto max-w-3xl p-8 text-slate-900">
        <div class="mb-8 flex justify-between border-b pb-4">
            <div>
                <h1 class="text-xl font-bold">{{ company.name }}</h1>
                <p class="text-sm">{{ company.address }}</p>
                <p class="text-sm">{{ company.phone }}</p>
            </div>
            <div class="text-right">
                <h2 class="text-lg font-semibold">Sales Invoice</h2>
                <p>{{ invoice.invoice_number }}</p>
                <p class="text-sm">Date: {{ invoice.invoice_date }}</p>
            </div>
        </div>
        <div class="mb-6 text-sm">
            <strong>Bill to:</strong> {{ invoice.customer?.name }}<br />
            {{ invoice.customer?.address }}<br />
            {{ invoice.customer?.phone }}
        </div>
        <table class="mb-6 w-full border-collapse text-sm">
            <thead>
                <tr class="border-b">
                    <th class="py-2 text-left">Description</th>
                    <th class="py-2 text-right">Qty</th>
                    <th class="py-2 text-right">Price</th>
                    <th class="py-2 text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="line in invoice.lines" :key="line.id" class="border-b">
                    <td class="py-2">{{ line.material?.name }} <span v-if="line.size_label" class="text-gray-500">({{ line.size_label }})</span></td>
                    <td class="py-2 text-right">{{ line.quantity }}</td>
                    <td class="py-2 text-right">{{ line.unit_price }}</td>
                    <td class="py-2 text-right">{{ line.line_total }}</td>
                </tr>
            </tbody>
        </table>
        <div class="text-right text-sm">
            <p>Subtotal: {{ invoice.subtotal }}</p>
            <p>Tax: {{ invoice.tax_total }}</p>
            <p class="text-lg font-bold">Grand total: {{ invoice.grand_total }}</p>
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        margin: 1cm;
    }
}
</style>