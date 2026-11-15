<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, purchaseReturn: Object, canManage: Boolean });

const postReturn = () => router.post(route('purchases.returns.post', props.purchaseReturn.id));
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="purchaseReturn.return_number">
            <template #actions>
                <ErpStatusBadge :status="purchaseReturn.status" class="mr-2" />
                <button v-if="canManage && purchaseReturn.status === 'draft'" type="button" class="erp-btn-primary" @click="postReturn">Post return</button>
            </template>
        </ErpPageHeader>
        <ErpDataTable
            :columns="[
                { key: 'material', label: 'Material' },
                { key: 'qty', label: 'Qty' },
                { key: 'total', label: 'Line total' },
            ]"
            :rows="
                (purchaseReturn.lines || []).map((l) => ({
                    material: l.material?.name,
                    qty: l.quantity,
                    total: l.line_total,
                }))
            "
        />
    </ErpLayout>
</template>