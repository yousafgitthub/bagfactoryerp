<script setup>
import ErpFormSection from '@/Components/Erp/ErpFormSection.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, order: Object, lookups: Object });

const variants = computed(() => {
    const product = props.lookups.products.find((p) => p.id === Number(form.bag_product_id));
    return product?.variants ?? [];
});

const form = useForm({
    bag_product_id: props.order?.bag_product_id ?? '',
    bag_product_variant_id: props.order?.bag_product_variant_id ?? '',
    bag_bill_of_material_id: props.order?.bag_bill_of_material_id ?? '',
    planned_quantity: props.order?.planned_quantity ?? 1000,
    machine_id: props.order?.machine_id ?? '',
    production_hall_id: props.order?.production_hall_id ?? '',
    color_id: props.order?.color_id ?? '',
    planned_start_date: props.order?.planned_start_date ?? '',
    planned_end_date: props.order?.planned_end_date ?? '',
    finished_goods_warehouse_id: props.order?.finished_goods_warehouse_id ?? '',
    dana_warehouse_id: props.order?.dana_warehouse_id ?? '',
    materials_warehouse_id: props.order?.materials_warehouse_id ?? '',
    rejected_warehouse_id: props.order?.rejected_warehouse_id ?? '',
    waste_warehouse_id: props.order?.waste_warehouse_id ?? '',
    notes: props.order?.notes ?? '',
    assignments: props.order?.assignments ?? [],
});

const submit = () => {
    if (props.order?.id) form.put(route('bag.orders.update', props.order.id));
    else form.post(route('bag.orders.store'));
};
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="pageTitle" />
        <form class="space-y-4" @submit.prevent="submit">
            <ErpFormSection title="Product">
                <select v-model="form.bag_product_id" class="erp-input" required>
                    <option v-for="p in lookups.products" :key="p.id" :value="p.id">{{ p.code }} — {{ p.name }}</option>
                </select>
                <select v-model="form.bag_product_variant_id" class="erp-input" required>
                    <option v-for="v in variants" :key="v.id" :value="v.id">{{ v.variant_code }} — {{ v.name }}</option>
                </select>
                <input v-model="form.planned_quantity" type="number" class="erp-input" required />
            </ErpFormSection>
            <ErpFormSection title="Warehouses">
                <select v-model="form.finished_goods_warehouse_id" class="erp-input" required>
                    <option v-for="w in lookups.warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                </select>
                <select v-model="form.dana_warehouse_id" class="erp-input" required>
                    <option v-for="w in lookups.warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                </select>
                <select v-model="form.materials_warehouse_id" class="erp-input" required>
                    <option v-for="w in lookups.warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                </select>
            </ErpFormSection>
            <button type="submit" class="erp-btn-primary" :disabled="form.processing">Save order</button>
        </form>
    </ErpLayout>
</template>