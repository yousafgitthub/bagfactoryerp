<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, canManage: Boolean });
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Suppliers" description="Vendor master data, balances, and ledger history.">
            <template #actions>
                <Link v-if="canManage" :href="route('purchases.suppliers.create')" class="erp-btn-primary">+ Supplier</Link>
                <Link :href="route('purchases.suppliers.index')" class="erp-btn-secondary">Browse suppliers</Link>
            </template>
        </ErpPageHeader>
    </ErpLayout>
</template>