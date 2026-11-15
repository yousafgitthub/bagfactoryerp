<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, products: Object, canManage: Boolean });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Bag products">
            <template #actions>
                <Link v-if="canManage" :href="route('bag.products.create')" class="erp-btn-primary">+ New product</Link>
            </template>
        </ErpPageHeader>
        <ErpDataTable
            title="Products"
            :columns="[
                { key: 'code', label: 'Code' },
                { key: 'name', label: 'Name' },
                { key: 'variants', label: 'Variants' },
                { key: 'status', label: 'Status' },
            ]"
            :rows="
                (products.data ?? []).map((p) => ({
                    code: p.code,
                    name: p.name,
                    variants: p.variants?.length ?? 0,
                    status: p.status,
                    href: route('bag.products.edit', p.id),
                }))
            "
        />
    </ErpLayout>
</template>