<script setup>
import ErpFormSection from '@/Components/Erp/ErpFormSection.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, product: Object, lookups: Object, bagTypes: Object });

const form = useForm({
    code: props.product?.code ?? '',
    name: props.product?.name ?? '',
    bag_type: props.product?.bag_type ?? '',
    size_label: props.product?.size_label ?? '',
    width_mm: props.product?.width_mm ?? '',
    length_mm: props.product?.length_mm ?? '',
    thickness_micron: props.product?.thickness_micron ?? '',
    nominal_weight_per_bag_g: props.product?.nominal_weight_per_bag_g ?? '',
    color_id: props.product?.color_id ?? '',
    unit_of_measurement_id: props.product?.unit_of_measurement_id ?? props.lookups.units[0]?.id ?? '',
    selling_price: props.product?.selling_price ?? 0,
    status: props.product?.status ?? 'active',
    description: props.product?.description ?? '',
    variants:
        props.product?.variants?.map((v) => ({
            id: v.id,
            variant_code: v.variant_code,
            name: v.name,
            weight_per_bag_g: v.weight_per_bag_g ?? '',
            saleable_material_id: v.saleable_material_id ?? '',
            rejected_material_id: v.rejected_material_id ?? '',
            status: v.status,
        })) ?? [{ variant_code: 'STD', name: 'Standard', saleable_material_id: '', rejected_material_id: '', status: 'active' }],
});

const addVariant = () => form.variants.push({ variant_code: '', name: '', saleable_material_id: '', rejected_material_id: '', status: 'active' });

const submit = () => {
    if (props.product?.id) form.put(route('bag.products.update', props.product.id));
    else form.post(route('bag.products.store'));
};
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="pageTitle" />
        <form class="space-y-4" @submit.prevent="submit">
            <ErpFormSection title="Product">
                <input v-model="form.code" class="erp-input" required placeholder="Code" />
                <input v-model="form.name" class="erp-input" required placeholder="Name" />
                <select v-model="form.bag_type" class="erp-input">
                    <option value="">Bag type</option>
                    <option v-for="(label, key) in bagTypes" :key="key" :value="key">{{ label }}</option>
                </select>
                <input v-model="form.nominal_weight_per_bag_g" type="number" step="0.0001" class="erp-input" placeholder="Nominal g/bag" />
                <select v-model="form.unit_of_measurement_id" class="erp-input" required>
                    <option v-for="u in lookups.units" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
            </ErpFormSection>
            <ErpFormSection title="Variants">
                <div v-for="(v, idx) in form.variants" :key="idx" class="grid gap-2 border-b border-slate-100 pb-3 dark:border-slate-800 sm:grid-cols-2">
                    <input v-model="v.variant_code" class="erp-input" placeholder="Variant code" />
                    <input v-model="v.name" class="erp-input" placeholder="Variant name" />
                    <select v-model="v.saleable_material_id" class="erp-input">
                        <option value="">Saleable material</option>
                        <option v-for="m in lookups.materials.filter((x) => x.inventory_bucket === 'finished_bag')" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                    <select v-model="v.rejected_material_id" class="erp-input">
                        <option value="">Rejected material</option>
                        <option v-for="m in lookups.materials.filter((x) => x.inventory_bucket === 'rejected_bag')" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </div>
                <button type="button" class="erp-btn-secondary" @click="addVariant">+ Variant</button>
            </ErpFormSection>
            <button type="submit" class="erp-btn-primary">Save product</button>
        </form>
    </ErpLayout>
</template>