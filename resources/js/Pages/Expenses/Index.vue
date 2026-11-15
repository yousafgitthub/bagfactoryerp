<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, expenses: Object, canManage: Boolean });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Expense entries">
            <template #actions>
                <Link v-if="canManage" :href="route('expenses.entries.create')" class="erp-btn-primary">+ Expense</Link>
            </template>
        </ErpPageHeader>
        <ErpDataTable
            :columns="[
                { key: 'number', label: 'Number' },
                { key: 'category', label: 'Category' },
                { key: 'date', label: 'Date' },
                { key: 'amount', label: 'Amount' },
                { key: 'status', label: 'Payment' },
            ]"
            :rows="
                (expenses.data || []).map((e) => ({
                    number: e.expense_number,
                    category: e.category?.name,
                    date: e.expense_date,
                    amount: e.amount,
                    status: e.payment_status,
                }))
            "
        />
    </ErpLayout>
</template>