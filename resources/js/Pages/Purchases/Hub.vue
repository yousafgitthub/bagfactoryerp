<script setup>
import ErpModuleIntro from '@/Components/Erp/ErpModuleIntro.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    links: Array,
    stockPolicy: String,
    canManage: Boolean,
});
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Purchases" help-text="Order from suppliers, receive goods on a GRN to increase stock, match supplier bills, and pay outstanding invoices.">
            <template #actions>
                <Link v-if="canManage" :href="route('purchases.orders.create')" class="erp-btn-primary">+ Purchase order</Link>
            </template>
        </ErpPageHeader>

        <ErpModuleIntro
            title="Purchase flow"
            :steps="[
                'Raise and confirm a purchase order with the supplier.',
                'Post a goods receipt (GRN) when material arrives — stock increases here, not on the invoice.',
                'Record the supplier invoice and allocate payments against posted bills.',
            ]"
        />

        <p class="mb-4 text-sm text-erp-muted">
            Stock recognition: <strong>{{ stockPolicy === 'goods_receipt' ? 'Goods receipt (GRN)' : stockPolicy }}</strong>.
            Purchase invoices update supplier payable only.
        </p>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Link v-for="item in links" :key="item.href" :href="item.href" class="erp-card block p-5 transition hover:ring-2 hover:ring-erp-primary/30">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">{{ item.label }}</h3>
                <p class="mt-1 text-sm text-erp-muted">{{ item.description }}</p>
            </Link>
        </div>
    </ErpLayout>
</template>