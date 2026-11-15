<script setup>
import ErpFormSection from '@/Components/Erp/ErpFormSection.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    material: Object,
    lookups: Object,
});

const form = useForm({
    code: props.material?.code ?? '',
    name: props.material?.name ?? '',
    material_category_id: props.material?.material_category_id ?? '',
    unit_of_measurement_id: props.material?.unit_of_measurement_id ?? '',
    default_unit_cost: props.material?.default_unit_cost ?? 0,
    reorder_level: props.material?.reorder_level ?? 0,
    inventory_bucket: props.material?.inventory_bucket ?? 'raw',
    supports_variants: props.material?.supports_variants ?? false,
    description: props.material?.description ?? '',
    status: props.material?.status ?? 'active',
});

const submit = () => {
    if (props.material?.id) {
        form.put(route('inventory.materials.update', props.material.id));
    } else {
        form.post(route('inventory.materials.store'));
    }
};
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="pageTitle" />

        <form class="erp-card max-w-3xl p-6" @submit.prevent="submit">
            <ErpFormSection title="Identity">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="erp-label">
                        Code
                        <input v-model="form.code" class="erp-input" required />
                    </label>
                    <label class="erp-label">
                        Name
                        <input v-model="form.name" class="erp-input" required />
                    </label>
                    <label class="erp-label">
                        Category
                        <select v-model="form.material_category_id" class="erp-input" required>
                            <option value="" disabled>Select</option>
                            <option v-for="c in lookups.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </label>
                    <label class="erp-label">
                        Base unit
                        <select v-model="form.unit_of_measurement_id" class="erp-input" required>
                            <option value="" disabled>Select</option>
                            <option v-for="u in lookups.units" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </label>
                </div>
            </ErpFormSection>

            <ErpFormSection title="Stock settings" class="mt-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="erp-label">
                        Default unit cost
                        <input v-model="form.default_unit_cost" type="number" step="0.0001" class="erp-input" />
                    </label>
                    <label class="erp-label">
                        Minimum / reorder level
                        <input v-model="form.reorder_level" type="number" step="0.0001" class="erp-input" />
                    </label>
                    <label class="erp-label">
                        Inventory bucket
                        <select v-model="form.inventory_bucket" class="erp-input">
                            <option v-for="(label, key) in lookups.buckets" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </label>
                    <label class="erp-label flex items-center gap-2 pt-6">
                        <input v-model="form.supports_variants" type="checkbox" class="rounded" />
                        Supports color variants
                    </label>
                </div>
            </ErpFormSection>

            <div class="mt-6 flex gap-2">
                <button type="submit" class="erp-btn-primary" :disabled="form.processing">Save</button>
            </div>
        </form>
    </ErpLayout>
</template>