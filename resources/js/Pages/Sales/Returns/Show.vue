<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, salesReturn: Object, canManage: Boolean });

const postReturn = () => {
    if (confirm('Post return? Saleable lines increase FG stock; damaged lines go to rejected inventory.')) {
        router.post(route('sales.returns.post', props.salesReturn.id));
    }
};
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="salesReturn.return_number">
            <template #actions>
                <button v-if="canManage && salesReturn.status === 'draft'" type="button" class="erp-btn-primary" @click="postReturn">Post return</button>
            </template>
        </ErpPageHeader>
        <ErpDataTable
            :columns="[
                { key: 'material', label: 'Material' },
                { key: 'qty', label: 'Qty' },
                { key: 'condition', label: 'Condition' },
                { key: 'disposition', label: 'Disposition' },
            ]"
            :rows="
                (salesReturn.lines || []).map((l) => ({
                    material: l.material?.name,
                    qty: l.quantity,
                    condition: l.condition,
                    disposition: l.disposition ?? '—',
                }))
            "
        />
    </ErpLayout>
</template>