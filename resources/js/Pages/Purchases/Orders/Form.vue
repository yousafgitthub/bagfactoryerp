<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, order: Object, lookups: Object });

const defaultLine = () => ({
    material_id: '',
    material_variant_id: null,
    unit_of_measurement_id: '',
    ordered_qty: 1,
    unit_price: 0,
    discount_amount: 0,
    tax_rate: 0,
});

const form = useForm({
    supplier_id: props.order?.supplier_id ?? '',
    warehouse_id: props.order?.warehouse_id ?? '',
    order_date: props.order?.order_date ?? new Date().toISOString().slice(0, 10),
    expected_date: props.order?.expected_date ?? '',
    notes: props.order?.notes ?? '',
    lines: props.order?.lines?.length
        ? props.order.lines.map((l) => ({
              material_id: l.material_id,
              material_variant_id: l.material_variant_id,
              unit_of_measurement_id: l.unit_of_measurement_id,
              ordered_qty: l.ordered_qty,
              unit_price: l.unit_price,
              discount_amount: l.discount_amount,
              tax_rate: l.tax_rate,
          }))
        : [defaultLine()],
});

const onMaterialChange = (line) => {
    const mat = props.lookups.materials.find((m) => m.id === Number(line.material_id));
    if (mat?.unit_of_measurement_id) {
        line.unit_of_measurement_id = mat.unit_of_measurement_id;
    }
};

const submit = () => {
    if (props.order) {
        form.put(route('purchases.orders.update', props.order.id));
    } else {
        form.post(route('purchases.orders.store'));
    }
};
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="pageTitle" />
        <form class="erp-card space-y-4 p-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium">Supplier</label>
                    <select v-model="form.supplier_id" class="erp-input w-full" required>
                        <option value="">Select…</option>
                        <option v-for="s in lookups.suppliers" :key="s.id" :value="s.id">{{ s.code }} — {{ s.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium">Warehouse</label>
                    <select v-model="form.warehouse_id" class="erp-input w-full" required>
                        <option value="">Select…</option>
                        <option v-for="w in lookups.warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium">Order date</label>
                    <input v-model="form.order_date" type="date" class="erp-input w-full" required />
                </div>
                <div>
                    <label class="text-sm font-medium">Expected date</label>
                    <input v-model="form.expected_date" type="date" class="erp-input w-full" />
                </div>
            </div>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-medium">Lines</h3>
                    <button type="button" class="erp-btn-secondary text-sm" @click="form.lines.push(defaultLine())">+ Line</button>
                </div>
                <div v-for="(line, idx) in form.lines" :key="idx" class="grid gap-2 rounded border border-erp-border p-3 sm:grid-cols-6">
                    <select v-model="line.material_id" class="erp-input sm:col-span-2" required @change="onMaterialChange(line)">
                        <option value="">Material</option>
                        <option v-for="m in lookups.materials" :key="m.id" :value="m.id">{{ m.code }}</option>
                    </select>
                    <input v-model="line.ordered_qty" type="number" step="0.0001" class="erp-input" placeholder="Qty" required />
                    <input v-model="line.unit_price" type="number" step="0.01" class="erp-input" placeholder="Unit price" required />
                    <input v-model="line.tax_rate" type="number" step="0.01" class="erp-input" placeholder="Tax %" />
                    <select v-model="line.unit_of_measurement_id" class="erp-input" required>
                        <option v-for="u in lookups.units" :key="u.id" :value="u.id">{{ u.code }}</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="erp-btn-primary" :disabled="form.processing">Save draft</button>
        </form>
    </ErpLayout>
</template>