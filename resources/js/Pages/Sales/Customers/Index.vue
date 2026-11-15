<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, customers: Object, filters: Object, canManage: Boolean });
const search = ref(props.filters?.search ?? '');
const applyFilters = () => router.get(route('sales.customers.index'), { search: search.value }, { preserveState: true });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Customers">
            <template #actions>
                <Link v-if="canManage" :href="route('sales.customers.create')" class="erp-btn-primary">+ Customer</Link>
            </template>
        </ErpPageHeader>
        <div class="mb-4 flex gap-2">
            <input v-model="search" type="search" class="erp-input max-w-xs" placeholder="Search" @keyup.enter="applyFilters" />
            <button type="button" class="erp-btn-secondary" @click="applyFilters">Search</button>
        </div>
        <ErpDataTable
            :columns="[
                { key: 'code', label: 'Code' },
                { key: 'name', label: 'Name' },
                { key: 'balance', label: 'Receivable' },
                { key: 'status', label: 'Status' },
                { key: 'actions', label: '' },
            ]"
            :rows="
                (customers.data || []).map((c) => ({
                    id: c.id,
                    code: c.code,
                    name: c.name,
                    balance: c.current_balance,
                    status: c.status,
                }))
            "
        >
            <template #cell-status="{ row }"><ErpStatusBadge :status="row.status" /></template>
            <template #cell-actions="{ row }">
                <Link :href="route('sales.customers.show', row.id)" class="erp-link text-sm">Profile</Link>
            </template>
        </ErpDataTable>
    </ErpLayout>
</template>