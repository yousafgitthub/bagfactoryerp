<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, accounts: Array, filters: Object, lines: Array });

const accountId = ref(props.filters.gl_account_id ?? '');
const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');

const apply = () =>
    router.get(route('accounting.reports.ledger'), {
        gl_account_id: accountId.value || undefined,
        from: from.value || undefined,
        to: to.value || undefined,
    });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="General ledger" />
        <div class="mb-4 flex flex-wrap gap-2">
            <select v-model="accountId" class="erp-input">
                <option value="">Select account…</option>
                <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.code }} — {{ a.name }}</option>
            </select>
            <input v-model="from" type="date" class="erp-input" />
            <input v-model="to" type="date" class="erp-input" />
            <button type="button" class="erp-btn-secondary" @click="apply">Apply</button>
        </div>
        <ErpDataTable
            v-if="lines.length"
            :columns="[
                { key: 'date', label: 'Date' },
                { key: 'je', label: 'Journal' },
                { key: 'debit', label: 'Debit' },
                { key: 'credit', label: 'Credit' },
                { key: 'memo', label: 'Memo' },
            ]"
            :rows="
                lines.map((l) => ({
                    date: l.entry?.entry_date,
                    je: l.entry?.entry_number,
                    debit: l.debit,
                    credit: l.credit,
                    memo: l.memo,
                }))
            "
        />
    </ErpLayout>
</template>