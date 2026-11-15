<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, filters: Object, rows: Array });

const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');

const apply = () => router.get(route('accounting.reports.trial-balance'), { from: from.value || undefined, to: to.value || undefined });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Trial balance" />
        <div class="mb-4 flex flex-wrap gap-2">
            <input v-model="from" type="date" class="erp-input" />
            <input v-model="to" type="date" class="erp-input" />
            <button type="button" class="erp-btn-secondary" @click="apply">Apply</button>
        </div>
        <ErpDataTable
            :columns="[
                { key: 'code', label: 'Account' },
                { key: 'debit', label: 'Debit' },
                { key: 'credit', label: 'Credit' },
                { key: 'balance', label: 'Balance' },
            ]"
            :rows="
                rows.map((r) => ({
                    code: `${r.account.code} ${r.account.name}`,
                    debit: r.debit,
                    credit: r.credit,
                    balance: r.balance,
                }))
            "
        />
    </ErpLayout>
</template>