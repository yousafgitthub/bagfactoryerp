<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, loans: Object, canManage: Boolean });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Company loans">
            <template #actions>
                <Link v-if="canManage" :href="route('accounting.company-loans.create')" class="erp-btn-primary">+ Loan</Link>
            </template>
        </ErpPageHeader>
        <ErpDataTable
            :columns="[
                { key: 'number', label: 'Loan' },
                { key: 'direction', label: 'Direction' },
                { key: 'counterparty', label: 'Counterparty' },
                { key: 'principal', label: 'Principal' },
                { key: 'outstanding', label: 'Outstanding' },
                { key: 'status', label: 'Status' },
            ]"
            :rows="
                (loans.data || []).map((l) => ({
                    number: l.loan_number,
                    direction: l.direction,
                    counterparty: l.counterparty_name,
                    principal: l.principal,
                    outstanding: l.outstanding_principal,
                    status: l.status,
                }))
            "
        />
    </ErpLayout>
</template>