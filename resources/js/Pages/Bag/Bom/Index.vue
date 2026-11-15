<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ pageTitle: String, breadcrumbs: Array, boms: Object, canManage: Boolean });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Bill of materials">
            <template #actions>
                <Link v-if="canManage" :href="route('bag.bom.create')" class="erp-btn-primary">+ New BOM</Link>
            </template>
        </ErpPageHeader>
        <ErpDataTable
            title="BOM versions"
            :columns="[
                { key: 'code', label: 'Code' },
                { key: 'product', label: 'Product' },
                { key: 'version', label: 'Ver' },
                { key: 'status', label: 'Status' },
            ]"
            :rows="
                (boms.data ?? []).map((b) => ({
                    code: b.code,
                    product: b.product?.name,
                    version: b.version,
                    status: b.status,
                    href: route('bag.bom.edit', b.id),
                }))
            "
        />
    </ErpLayout>
</template>