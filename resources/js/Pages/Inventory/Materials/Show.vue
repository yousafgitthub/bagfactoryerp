<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    material: Object,
    canManage: Boolean,
});

const variantForm = useForm({
    variant_code: '',
    name: '',
    color_id: '',
    status: 'active',
});

const addVariant = () => {
    variantForm.post(route('inventory.materials.variants.store', props.material.id), {
        onSuccess: () => variantForm.reset(),
    });
};
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="material.name" :description="material.code">
            <template #actions>
                <Link v-if="canManage" :href="route('inventory.materials.edit', material.id)" class="erp-btn-secondary">Edit</Link>
            </template>
        </ErpPageHeader>

        <div class="erp-card mb-4 grid gap-2 p-4 text-sm sm:grid-cols-2">
            <div><span class="text-erp-muted">Category:</span> {{ material.category?.name }}</div>
            <div><span class="text-erp-muted">Unit:</span> {{ material.unit?.name }}</div>
            <div><span class="text-erp-muted">Reorder:</span> {{ material.reorder_level }}</div>
            <div><span class="text-erp-muted">Bucket:</span> {{ material.inventory_bucket }}</div>
        </div>

        <div v-if="material.supports_variants && canManage" class="erp-card mb-4 p-4">
            <h3 class="font-semibold">Add variant</h3>
            <form class="mt-2 flex flex-wrap gap-2" @submit.prevent="addVariant">
                <input v-model="variantForm.variant_code" class="erp-input" placeholder="Variant code" required />
                <input v-model="variantForm.name" class="erp-input" placeholder="Name" required />
                <button type="submit" class="erp-btn-primary" :disabled="variantForm.processing">Add</button>
            </form>
        </div>

        <ErpDataTable
            title="Stock by warehouse"
            :columns="[
                { key: 'warehouse', label: 'Warehouse' },
                { key: 'quantity', label: 'Qty' },
                { key: 'avg', label: 'Avg cost' },
            ]"
            :rows="
                (material.balances ?? []).map((b) => ({
                    warehouse: b.warehouse?.name,
                    quantity: b.quantity,
                    avg: b.average_unit_cost,
                }))
            "
            empty-message="No stock on hand."
        />
    </ErpLayout>
</template>