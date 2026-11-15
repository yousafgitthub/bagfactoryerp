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
    consumptionTypes: Object,
    preselectedOrderId: Number,
});

const defaultConsumptions = () =>
    props.batch?.material_consumptions?.map((i) => ({
        material_id: i.material_id,
        material_variant_id: i.material_variant_id ?? '',
        consumption_type: i.consumption_type,
        quantity: i.quantity,
    })) ?? [];

const defaultOperators = () =>
    props.batch?.operators?.map((o) => ({
        operator_name: o.operator_name,
        labor_cost: o.labor_cost,
        hours: o.hours ?? '',
    })) ?? [{ operator_name: '', labor_cost: 0, hours: '' }];

const form = useForm({
    bag_production_order_id: props.batch?.bag_production_order_id ?? props.preselectedOrderId ?? '',
    production_date: props.batch?.production_date ?? new Date().toISOString().slice(0, 10),
    machine_id: props.batch?.machine_id ?? '',
    production_hall_id: props.batch?.production_hall_id ?? '',
    bag_bill_of_material_id: props.batch?.bag_bill_of_material_id ?? '',
    good_quantity: props.batch?.good_quantity ?? 0,
    rejected_quantity: props.batch?.rejected_quantity ?? 0,
    dana_consumed_kg: props.batch?.dana_consumed_kg ?? 0,
    color_material_consumed_kg: props.batch?.color_material_consumed_kg ?? 0,
    actual_weight_per_good_bag_g: props.batch?.actual_weight_per_good_bag_g ?? '',
    reusable_waste_kg: props.batch?.reusable_waste_kg ?? 0,
    scrap_waste_kg: props.batch?.scrap_waste_kg ?? 0,
    process_loss_kg: props.batch?.process_loss_kg ?? 0,
    electricity_cost: props.batch?.electricity_cost ?? 0,
    other_overhead_cost: props.batch?.other_overhead_cost ?? 0,
    finished_goods_warehouse_id: props.batch?.finished_goods_warehouse_id ?? '',
    dana_warehouse_id: props.batch?.dana_warehouse_id ?? '',
    materials_warehouse_id: props.batch?.materials_warehouse_id ?? '',
    rejected_warehouse_id: props.batch?.rejected_warehouse_id ?? '',
    waste_warehouse_id: props.batch?.waste_warehouse_id ?? '',
    production_time_hours: props.batch?.production_time_hours ?? '',
    notes: props.batch?.notes ?? '',
    consumptions: defaultConsumptions(),
    operators: defaultOperators(),
});

const addOperator = () => form.operators.push({ operator_name: '', labor_cost: 0, hours: '' });

const onOrderChange = () => {
    const order = props.lookups.orders.find((o) => o.id === Number(form.bag_production_order_id));
    if (!order) return;
    form.finished_goods_warehouse_id = order.finished_goods_warehouse_id;
    form.dana_warehouse_id = order.dana_warehouse_id;
    form.materials_warehouse_id = order.materials_warehouse_id;
    form.rejected_warehouse_id = order.rejected_warehouse_id ?? '';
    form.waste_warehouse_id = order.waste_warehouse_id ?? '';
    if (order.machine_id) form.machine_id = order.machine_id;
    if (order.production_hall_id) form.production_hall_id = order.production_hall_id;
};

const submit = () => {
    if (props.batch?.id) {
        form.put(route('bag.batches.update', props.batch.id));
    } else {
        form.post(route('bag.batches.store'));
    }
};
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="pageTitle" />
        <form class="space-y-4" @submit.prevent="submit">
            <ErpFormSection title="Order & schedule">
                <select v-model="form.bag_production_order_id" class="erp-input" required @change="onOrderChange">
                    <option value="">Select order</option>
                    <option v-for="o in lookups.orders" :key="o.id" :value="o.id">
                        {{ o.order_number }} — {{ o.product?.name }} ({{ o.variant?.variant_code }})
                    </option>
                </select>
                <input v-model="form.production_date" type="date" class="erp-input" required />
                <select v-model="form.machine_id" class="erp-input" required>
                    <option v-for="m in lookups.machines" :key="m.id" :value="m.id">{{ m.name }}</option>
                </select>
                <select v-model="form.production_hall_id" class="erp-input" required>
                    <option v-for="h in lookups.halls" :key="h.id" :value="h.id">{{ h.name }}</option>
                </select>
            </ErpFormSection>

            <ErpFormSection title="Output & Dana">
                <input v-model="form.good_quantity" type="number" step="1" class="erp-input" placeholder="Good bags" />
                <input v-model="form.rejected_quantity" type="number" step="1" class="erp-input" placeholder="Rejected" />
                <input v-model="form.dana_consumed_kg" type="number" step="0.0001" class="erp-input" required placeholder="Dana KG" />
                <input v-model="form.actual_weight_per_good_bag_g" type="number" step="0.0001" class="erp-input" placeholder="Actual g/bag" />
                <input v-model="form.process_loss_kg" type="number" step="0.0001" class="erp-input" placeholder="Process loss KG" />
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

            <button type="submit" class="erp-btn-primary" :disabled="form.processing">Save batch</button>
        </form>
    </ErpLayout>
</template>