<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, payments: Object, canManage: Boolean });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Supplier payments"
            help-text="Each payment can settle one or more posted purchase invoices. Allocate the full amount before saving."
        >
            <template #actions>
                <Link v-if="canManage" :href="route('purchases.payments.create')" class="erp-btn-primary">+ Payment</Link>
            </template>
        </ErpPageHeader>
        <ErpDataTable
            :columns="[
                { key: 'number', label: 'Payment' },
                { key: 'supplier', label: 'Supplier' },
                { key: 'date', label: 'Date' },
                { key: 'amount', label: 'Amount' },
                { key: 'method', label: 'Method' },
            ]"
            empty-message="No supplier payments recorded."
            empty-description="Record a payment when you pay a supplier against open invoices."
            :rows="
                (payments.data || []).map((p) => ({
                    number: p.payment_number,
                    supplier: p.supplier?.name,
                    date: p.payment_date,
                    amount: p.amount,
                    method: p.payment_method,
                }))
            "
        />
    </ErpLayout>
</template>