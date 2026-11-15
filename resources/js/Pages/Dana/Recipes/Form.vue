<script setup>
import ErpFormSection from '@/Components/Erp/ErpFormSection.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    recipe: Object,
    lookups: Object,
    inputTypes: Object,
});

const defaultLines = () =>
    props.recipe?.lines?.map((l) => ({
        material_id: l.material_id,
        material_variant_id: l.material_variant_id ?? '',
        input_type: l.input_type,
        quantity_per_output_kg: l.quantity_per_output_kg,
    })) ?? [{ material_id: '', material_variant_id: '', input_type: 'raw', quantity_per_output_kg: '' }];

const form = useForm({
    code: props.recipe?.code ?? '',
    name: props.recipe?.name ?? '',
    output_material_id: props.recipe?.output_material_id ?? '',
    output_material_variant_id: props.recipe?.output_material_variant_id ?? '',
    max_process_loss_percent: props.recipe?.max_process_loss_percent ?? 5,
    description: props.recipe?.description ?? '',
    status: props.recipe?.status ?? 'active',
    lines: defaultLines(),
});

const addLine = () => form.lines.push({ material_id: '', material_variant_id: '', input_type: 'raw', quantity_per_output_kg: '' });

const submit = () => {
    if (props.recipe?.id) {
        form.put(route('dana.recipes.update', props.recipe.id));
    } else {
        form.post(route('dana.recipes.store'));
    }
};
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="pageTitle" />

        <form class="erp-card space-y-4 p-6" @submit.prevent="submit">
            <ErpFormSection title="Recipe header">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="erp-label">Code <input v-model="form.code" class="erp-input" required /></label>
                    <label class="erp-label">Name <input v-model="form.name" class="erp-input" required /></label>
                    <label class="erp-label">
                        Output material
                        <select v-model="form.output_material_id" class="erp-input" required>
                            <option v-for="m in lookups.materials" :key="m.id" :value="m.id">{{ m.name }}</option>
                        </select>
                    </label>
                    <label class="erp-label">
                        Max process loss %
                        <input v-model="form.max_process_loss_percent" type="number" step="0.01" class="erp-input" />
                    </label>
                </div>
            </ErpFormSection>

            <ErpFormSection title="Input ratios (per 1 KG Dana output)">
                <div v-for="(row, idx) in form.lines" :key="idx" class="mb-2 grid gap-2 sm:grid-cols-4">
                    <select v-model="row.material_id" class="erp-input" required>
                        <option v-for="m in lookups.materials" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                    <select v-model="row.input_type" class="erp-input">
                        <option v-for="(label, key) in inputTypes" :key="key" :value="key">{{ label }}</option>
                    </select>
                    <input v-model="row.quantity_per_output_kg" type="number" step="0.000001" class="erp-input" required />
                </div>
                <button type="button" class="erp-btn-secondary text-sm" @click="addLine">+ Add line</button>
            </ErpFormSection>

            <button type="submit" class="erp-btn-primary" :disabled="form.processing">Save recipe</button>
        </form>
    </ErpLayout>
</template>