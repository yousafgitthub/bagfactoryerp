<script setup>
import ErpFormSection from '@/Components/Erp/ErpFormSection.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    batch: Object,
    lookups: Object,
    inputTypes: Object,
});

const defaultInputs = () =>
  props.batch?.material_inputs?.map((i) => ({
      material_id: i.material_id,
      material_variant_id: i.material_variant_id ?? '',
      input_type: i.input_type,
      quantity_kg: i.quantity_kg,
  })) ?? [{ material_id: '', material_variant_id: '', input_type: 'raw', quantity_kg: '' }];

const defaultOperators = () =>
  props.batch?.operators?.map((o) => ({
      operator_name: o.operator_name,
      labor_cost: o.labor_cost,
      hours: o.hours ?? '',
  })) ?? [{ operator_name: '', labor_cost: 0, hours: '' }];

const form = useForm({
    production_date: props.batch?.production_date ?? new Date().toISOString().slice(0, 10),
    machine_id: props.batch?.machine_id ?? '',
    production_hall_id: props.batch?.production_hall_id ?? '',
    raw_material_warehouse_id: props.batch?.raw_material_warehouse_id ?? '',
    finished_dana_warehouse_id: props.batch?.finished_dana_warehouse_id ?? '',
    waste_warehouse_id: props.batch?.waste_warehouse_id ?? '',
    output_material_id: props.batch?.output_material_id ?? '',
    output_material_variant_id: props.batch?.output_material_variant_id ?? '',
    dana_production_recipe_id: props.batch?.dana_production_recipe_id ?? '',
    output_kg: props.batch?.output_kg ?? 0,
    reusable_waste_kg: props.batch?.reusable_waste_kg ?? 0,
    scrap_waste_kg: props.batch?.scrap_waste_kg ?? 0,
    process_loss_kg: props.batch?.process_loss_kg ?? 0,
    electricity_cost: props.batch?.electricity_cost ?? 0,
    other_overhead_cost: props.batch?.other_overhead_cost ?? 0,
    reusable_waste_material_id: props.batch?.reusable_waste_material_id ?? '',
    scrap_waste_material_id: props.batch?.scrap_waste_material_id ?? '',
    notes: props.batch?.notes ?? '',
    inputs: defaultInputs(),
    operators: defaultOperators(),
});

const addInput = () => form.inputs.push({ material_id: '', material_variant_id: '', input_type: 'raw', quantity_kg: '' });
const addOperator = () => form.operators.push({ operator_name: '', labor_cost: 0, hours: '' });

const variantsFor = (materialId) => {
    const material = props.lookups.materials.find((m) => m.id === Number(materialId));

    return material?.variants ?? [];
};

const onMachineChange = () => {
    const machine = props.lookups.machines.find((m) => m.id === Number(form.machine_id));
    if (machine?.production_hall_id) {
        form.production_hall_id = machine.production_hall_id;
    }
};

const submit = () => {
    if (props.batch?.id) {
        form.put(route('dana.batches.update', props.batch.id));
    } else {
        form.post(route('dana.batches.store'));
    }
};
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="pageTitle" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="erp-card p-6">
                <ErpFormSection title="Batch header">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="erp-label">
                            Production date
                            <input v-model="form.production_date" type="date" class="erp-input" required />
                        </label>
                        <label class="erp-label">
                            Recipe (optional)
                            <select v-model="form.dana_production_recipe_id" class="erp-input">
                                <option value="">—</option>
                                <option v-for="r in lookups.recipes" :key="r.id" :value="r.id">{{ r.name }}</option>
                            </select>
                        </label>
                        <label class="erp-label">
                            Machine
                            <select v-model="form.machine_id" class="erp-input" required @change="onMachineChange">
                                <option value="" disabled>Select</option>
                                <option v-for="m in lookups.machines" :key="m.id" :value="m.id">{{ m.name }}</option>
                            </select>
                        </label>
                        <label class="erp-label">
                            Production hall
                            <select v-model="form.production_hall_id" class="erp-input" required>
                                <option value="" disabled>Select</option>
                                <option v-for="h in lookups.halls" :key="h.id" :value="h.id">{{ h.name }}</option>
                            </select>
                        </label>
                        <label class="erp-label">
                            Raw warehouse
                            <select v-model="form.raw_material_warehouse_id" class="erp-input" required>
                                <option v-for="w in lookups.warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                            </select>
                        </label>
                        <label class="erp-label">
                            Finished Dana warehouse
                            <select v-model="form.finished_dana_warehouse_id" class="erp-input" required>
                                <option v-for="w in lookups.warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                            </select>
                        </label>
                        <label class="erp-label">
                            Waste warehouse
                            <select v-model="form.waste_warehouse_id" class="erp-input">
                                <option value="">—</option>
                                <option v-for="w in lookups.warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                            </select>
                        </label>
                        <label class="erp-label">
                            Output Dana material
                            <select v-model="form.output_material_id" class="erp-input" required>
                                <option v-for="m in lookups.materials" :key="m.id" :value="m.id">{{ m.name }}</option>
                            </select>
                        </label>
                        <label class="erp-label">
                            Output variant
                            <select v-model="form.output_material_variant_id" class="erp-input">
                                <option value="">—</option>
                                <option
                                    v-for="v in variantsFor(form.output_material_id)"
                                    :key="v.id"
                                    :value="v.id"
                                >
                                    {{ v.name }}
                                </option>
                            </select>
                        </label>
                    </div>
                </ErpFormSection>
            </div>

            <div class="erp-card p-6">
                <ErpFormSection title="Material inputs">
                    <div v-for="(row, idx) in form.inputs" :key="idx" class="mb-3 grid gap-2 sm:grid-cols-5">
                        <select v-model="row.material_id" class="erp-input" required>
                            <option value="" disabled>Material</option>
                            <option v-for="m in lookups.materials" :key="m.id" :value="m.id">{{ m.name }}</option>
                        </select>
                        <select v-model="row.input_type" class="erp-input">
                            <option v-for="(label, key) in inputTypes" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <input v-model="row.quantity_kg" type="number" step="0.0001" class="erp-input" placeholder="KG" required />
                        <select v-model="row.material_variant_id" class="erp-input">
                            <option value="">Variant</option>
                            <option v-for="v in variantsFor(row.material_id)" :key="v.id" :value="v.id">{{ v.name }}</option>
                        </select>
                    </div>
                    <button type="button" class="erp-btn-secondary text-sm" @click="addInput">+ Add line</button>
                </ErpFormSection>
            </div>

            <div class="erp-card p-6">
                <ErpFormSection title="Outputs, waste & costs">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <label class="erp-label">Output KG <input v-model="form.output_kg" type="number" step="0.0001" class="erp-input" required /></label>
                        <label class="erp-label">Reusable waste KG <input v-model="form.reusable_waste_kg" type="number" step="0.0001" class="erp-input" /></label>
                        <label class="erp-label">Scrap waste KG <input v-model="form.scrap_waste_kg" type="number" step="0.0001" class="erp-input" /></label>
                        <label class="erp-label">Process loss KG <input v-model="form.process_loss_kg" type="number" step="0.0001" class="erp-input" /></label>
                        <label class="erp-label">Electricity cost <input v-model="form.electricity_cost" type="number" step="0.01" class="erp-input" /></label>
                        <label class="erp-label">Other overhead <input v-model="form.other_overhead_cost" type="number" step="0.01" class="erp-input" /></label>
                        <label class="erp-label">
                            Reusable waste material
                            <select v-model="form.reusable_waste_material_id" class="erp-input">
                                <option value="">—</option>
                                <option v-for="m in lookups.materials" :key="m.id" :value="m.id">{{ m.name }}</option>
                            </select>
                        </label>
                        <label class="erp-label">
                            Scrap material
                            <select v-model="form.scrap_waste_material_id" class="erp-input">
                                <option value="">—</option>
                                <option v-for="m in lookups.materials" :key="m.id" :value="m.id">{{ m.name }}</option>
                            </select>
                        </label>
                    </div>
                </ErpFormSection>
            </div>

            <div class="erp-card p-6">
                <ErpFormSection title="Operators">
                    <div v-for="(row, idx) in form.operators" :key="idx" class="mb-3 grid gap-2 sm:grid-cols-3">
                        <input v-model="row.operator_name" class="erp-input" placeholder="Operator name" />
                        <input v-model="row.labor_cost" type="number" step="0.01" class="erp-input" placeholder="Labor cost" />
                        <input v-model="row.hours" type="number" step="0.25" class="erp-input" placeholder="Hours" />
                    </div>
                    <button type="button" class="erp-btn-secondary text-sm" @click="addOperator">+ Add operator</button>
                </ErpFormSection>
            </div>

            <label class="erp-label block">
                Notes
                <textarea v-model="form.notes" class="erp-input w-full" rows="3" />
            </label>

            <button type="submit" class="erp-btn-primary" :disabled="form.processing">Save draft</button>
        </form>
    </ErpLayout>
</template>