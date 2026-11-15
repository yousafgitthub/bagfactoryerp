<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    pageTitle: String,
    invoice: Object,
    company: Object,
    outstanding: Number,
    statusLabel: String,
    saleTypeLabel: String,
});

const printPage = () => window.print();

const goBack = () => window.history.back();

const lineDescription = (line) => {
    const parts = [line.material?.name];
    if (line.bag_product_variant?.name) {
        parts.push(line.bag_product_variant.name);
    } else if (line.bag_product?.name) {
        parts.push(line.bag_product.name);
    }
    const variant = [line.size_label, line.color_label].filter(Boolean).join(' · ');
    if (variant) {
        parts.push(variant);
    }

    return parts.filter(Boolean).join(' — ');
};

const lineSku = (line) => line.material?.code ?? line.bag_product_variant?.variant_code ?? '';

const money = (value) => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const hasCompanyField = (key) => {
    const value = props.company?.[key];

    return value !== null && value !== undefined && String(value).trim() !== '';
};

const showDiscount = computed(
    () =>
        Number(props.invoice?.discount_total) > 0
        || (props.invoice?.lines ?? []).some((l) => Number(l.discount_amount) > 0),
);

const showTax = computed(
    () =>
        Number(props.invoice?.tax_total) > 0
        || (props.invoice?.lines ?? []).some((l) => Number(l.tax_amount) > 0 || Number(l.tax_rate) > 0),
);
</script>

<template>
    <Head :title="pageTitle" />

    <div class="print-shell bg-white text-slate-900">
        <div class="no-print print-toolbar">
            <h1 class="text-lg font-semibold">{{ invoice.invoice_number }}</h1>
            <div class="flex gap-2">
                <button type="button" class="print-btn" @click="printPage">Print</button>
                <button type="button" class="print-btn-secondary" @click="goBack">Back</button>
            </div>
        </div>

        <article class="print-sheet mx-auto max-w-[210mm] px-[12mm] py-[10mm]">
            <header class="mb-6 flex flex-wrap items-start justify-between gap-4 border-b border-slate-300 pb-4">
                <div class="min-w-[45%]">
                    <img
                        v-if="company.logo_url"
                        :src="company.logo_url"
                        alt="Company logo"
                        class="mb-2 max-h-14 max-w-[180px] object-contain"
                    />
                    <h1 v-if="hasCompanyField('name')" class="text-xl font-bold">{{ company.name }}</h1>
                    <p v-if="hasCompanyField('address')" class="mt-1 text-sm whitespace-pre-line">{{ company.address }}</p>
                    <p v-if="hasCompanyField('phone')" class="text-sm">Tel: {{ company.phone }}</p>
                    <p v-if="hasCompanyField('email')" class="text-sm">Email: {{ company.email }}</p>
                    <p v-if="hasCompanyField('tax_registration')" class="text-sm">Tax ID: {{ company.tax_registration }}</p>
                </div>
                <div class="text-right text-sm">
                    <h2 class="text-lg font-semibold uppercase tracking-wide">Sales Invoice</h2>
                    <p class="mt-1 font-medium">{{ invoice.invoice_number }}</p>
                    <p>Invoice date: {{ invoice.invoice_date }}</p>
                    <p v-if="invoice.due_date">Due date: {{ invoice.due_date }}</p>
                    <p class="mt-1">Status: {{ statusLabel }}</p>
                    <p v-if="saleTypeLabel">Sale type: {{ saleTypeLabel }}</p>
                </div>
            </header>

            <section class="mb-6 grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <p class="mb-1 font-semibold uppercase text-slate-600">Bill to</p>
                    <p class="font-medium">{{ invoice.customer?.name }}</p>
                    <p v-if="invoice.customer?.contact_person">Contact: {{ invoice.customer.contact_person }}</p>
                    <p v-if="invoice.customer?.phone">Phone: {{ invoice.customer.phone }}</p>
                    <p v-if="invoice.customer?.address" class="whitespace-pre-line">{{ invoice.customer.address }}</p>
                </div>
                <div v-if="invoice.warehouse?.name" class="sm:text-right">
                    <p class="mb-1 font-semibold uppercase text-slate-600">Ship from</p>
                    <p>{{ invoice.warehouse.name }}</p>
                </div>
            </section>

            <table class="lines-table mb-6 w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-400 text-left">
                        <th class="py-2 pr-2">Item</th>
                        <th class="py-2 pr-2">Code</th>
                        <th class="py-2 pr-2 text-right">Qty</th>
                        <th class="py-2 pr-2">Unit</th>
                        <th class="py-2 pr-2 text-right">Unit price</th>
                        <th v-if="showDiscount" class="py-2 pr-2 text-right">Discount</th>
                        <th v-if="showTax" class="py-2 pr-2 text-right">Tax</th>
                        <th class="py-2 text-right">Line total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="line in invoice.lines" :key="line.id" class="line-row border-b border-slate-200">
                        <td class="py-2 pr-2 align-top">{{ lineDescription(line) }}</td>
                        <td class="py-2 pr-2 align-top text-slate-600">{{ lineSku(line) }}</td>
                        <td class="py-2 pr-2 text-right align-top">{{ line.quantity }}</td>
                        <td class="py-2 pr-2 align-top">{{ line.unit_of_measurement?.symbol || line.unit_of_measurement?.code || '—' }}</td>
                        <td class="py-2 pr-2 text-right align-top">{{ money(line.unit_price) }}</td>
                        <td v-if="showDiscount" class="py-2 pr-2 text-right align-top">{{ money(line.discount_amount) }}</td>
                        <td v-if="showTax" class="py-2 pr-2 text-right align-top">
                            <span v-if="Number(line.tax_rate) > 0">{{ line.tax_rate }}%</span>
                            <span v-else>{{ money(line.tax_amount) }}</span>
                        </td>
                        <td class="py-2 text-right align-top font-medium">{{ money(line.line_total) }}</td>
                    </tr>
                </tbody>
            </table>

            <section class="mb-8 flex justify-end">
                <div class="w-full max-w-xs space-y-1 text-sm">
                    <div class="flex justify-between gap-4">
                        <span>Subtotal</span>
                        <span>{{ money(invoice.subtotal) }}</span>
                    </div>
                    <div v-if="Number(invoice.discount_total) > 0" class="flex justify-between gap-4">
                        <span>Discount</span>
                        <span>{{ money(invoice.discount_total) }}</span>
                    </div>
                    <div v-if="Number(invoice.tax_total) > 0" class="flex justify-between gap-4">
                        <span>Tax</span>
                        <span>{{ money(invoice.tax_total) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-t border-slate-300 pt-2 text-base font-bold">
                        <span>Grand total</span>
                        <span>{{ money(invoice.grand_total) }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span>Amount paid</span>
                        <span>{{ money(invoice.amount_paid) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 font-semibold">
                        <span>Outstanding</span>
                        <span>{{ money(outstanding) }}</span>
                    </div>
                </div>
            </section>

            <section v-if="invoice.notes" class="mb-8 text-sm">
                <p class="mb-1 font-semibold uppercase text-slate-600">Notes / terms</p>
                <p class="whitespace-pre-line">{{ invoice.notes }}</p>
            </section>

            <footer class="signature-grid mt-12 grid gap-8 text-sm sm:grid-cols-2">
                <div>
                    <p class="mb-8 text-slate-600">Prepared by</p>
                    <div class="signature-line" />
                </div>
                <div>
                    <p class="mb-8 text-slate-600">Received by (customer)</p>
                    <div class="signature-line" />
                </div>
            </footer>
        </article>
    </div>
</template>

<style scoped>
.print-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.75rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.print-btn {
    border-radius: 0.375rem;
    background: #0f172a;
    color: #fff;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
}

.print-btn-secondary {
    border-radius: 0.375rem;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #0f172a;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.signature-line {
    border-top: 1px solid #334155;
    height: 1px;
}

@media print {
    .no-print {
        display: none !important;
    }

    .print-shell {
        background: #fff;
    }

    .print-sheet {
        max-width: none;
        padding: 0;
    }

    .line-row {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .lines-table thead {
        display: table-header-group;
    }

    .signature-grid {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}

@page {
    size: A4 portrait;
    margin: 12mm;
}
</style>