<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, invoice: Object, salesOrder: Object, lookups: Object });

const defaultLine = () => ({
    bag_product_variant_id: '',
    material_id: '',
    unit_of_measurement_id: props.lookups.units?.[0]?.id ?? '',
    quantity: 1,
    unit_price: 0,
    discount_amount: 0,
    tax_rate: 0,
});

const form = useForm({
    customer_id: props.salesOrder?.customer_id ?? '',
    sales_order_id: props.salesOrder?.id ?? null,
    warehouse_id: props.salesOrder?.warehouse_id ?? '',
    invoice_date: new Date().toISOString().slice(0, 10),
    due_date: '',
    sale_type: 'credit',
    notes: '',
    lines: [defaultLine()],
});

const onVariant = (line) => {
    const v = props.lookups.variants.find((x) => x.id === Number(line.bag_product_variant_id));
    if (v) {
        line.material_id = v.saleable_material_id;
        line.unit_of_measurement_id = props.lookups.materials.find((m) => m.id === v.saleable_material_id)?.unit_of_measurement_id ?? line.unit_of_measurement_id;
        line.unit_price = v.selling_price ?? line.unit_price;
    }
};

const submit = () => form.post(route('sales.invoices.store'));
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            :title="pageTitle"
            help-text="Save as draft first. Posting checks finished-bag stock in the selected warehouse."
        />
        <form class="erp-card space-y-4 p-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="text-sm font-medium">Customer</label>
                    <select v-model="form.customer_id" class="erp-input w-full" required>
                        <option v-for="c in lookups.customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium">Warehouse</label>
                    <select v-model="form.warehouse_id" class="erp-input w-full" required>
                        <option v-for="w in lookups.warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium">Sale type</label>
                    <select v-model="form.sale_type" class="erp-input w-full">
                        <option value="credit">Credit</option>
                        <option value="cash">Cash</option>
                    </select>
                </div>
            </div>
            <div v-for="(line, idx) in form.lines" :key="idx" class="grid gap-2 sm:grid-cols-4">
                <select v-model="line.bag_product_variant_id" class="erp-input" @change="onVariant(line)">
                    <option v-for="v in lookups.variants" :key="v.id" :value="v.id">{{ v.variant_code }}</option>
                </select>
                <input v-model="line.quantity" type="number" class="erp-input" required />
                <input v-model="line.unit_price" type="number" class="erp-input" required />
                <input v-model="line.tax_rate" type="number" class="erp-input" placeholder="Tax %" />
            </div>
            <button type="submit" class="erp-btn-primary">Save draft</button>
        </form>
    </ErpLayout>
</template>