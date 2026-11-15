<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, order: Object, lookups: Object });

const defaultLine = () => ({
    bag_product_variant_id: '',
    material_id: '',
    unit_of_measurement_id: props.lookups.units?.[0]?.id ?? '',
    ordered_qty: 1,
    unit_price: 0,
    discount_amount: 0,
    tax_rate: 0,
});

const form = useForm({
    customer_id: props.order?.customer_id ?? '',
    warehouse_id: props.order?.warehouse_id ?? '',
    order_date: props.order?.order_date ?? new Date().toISOString().slice(0, 10),
    expected_date: props.order?.expected_date ?? '',
    notes: props.order?.notes ?? '',
    lines: props.order?.lines?.length
        ? props.order.lines.map((l) => ({
              bag_product_variant_id: l.bag_product_variant_id,
              material_id: l.material_id,
              unit_of_measurement_id: l.unit_of_measurement_id,
              ordered_qty: l.ordered_qty,
              unit_price: l.unit_price,
              discount_amount: l.discount_amount,
              tax_rate: l.tax_rate,
          }))
        : [defaultLine()],
});

const onVariant = (line) => {
    const v = props.lookups.variants.find((x) => x.id === Number(line.bag_product_variant_id));
    if (v) {
        line.material_id = v.saleable_material_id;
        line.unit_of_measurement_id = v.product?.unit_of_measurement_id ?? line.unit_of_measurement_id;
        line.unit_price = v.selling_price ?? line.unit_price;
    }
};

const submit = () => {
    if (props.order) form.put(route('sales.orders.update', props.order.id));
    else form.post(route('sales.orders.store'));
};
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="pageTitle" />
        <form class="erp-card space-y-4 p-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium">Customer</label>
                    <select v-model="form.customer_id" class="erp-input w-full" required>
                        <option value="">Select…</option>
                        <option v-for="c in lookups.customers" :key="c.id" :value="c.id">{{ c.code }} — {{ c.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium">Warehouse</label>
                    <select v-model="form.warehouse_id" class="erp-input w-full" required>
                        <option v-for="w in lookups.warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                    </select>
                </div>
            </div>
            <div v-for="(line, idx) in form.lines" :key="idx" class="grid gap-2 rounded border border-erp-border p-3 sm:grid-cols-5">
                <select v-model="line.bag_product_variant_id" class="erp-input sm:col-span-2" @change="onVariant(line)">
                    <option value="">Variant</option>
                    <option v-for="v in lookups.variants" :key="v.id" :value="v.id">{{ v.variant_code }} — {{ v.name }}</option>
                </select>
                <input v-model="line.ordered_qty" type="number" step="0.0001" class="erp-input" required />
                <input v-model="line.unit_price" type="number" step="0.01" class="erp-input" required />
                <input v-model="line.tax_rate" type="number" step="0.01" class="erp-input" placeholder="Tax %" />
            </div>
            <button type="button" class="erp-btn-secondary text-sm" @click="form.lines.push(defaultLine())">+ Line</button>
            <button type="submit" class="erp-btn-primary block" :disabled="form.processing">Save</button>
        </form>
    </ErpLayout>
</template>