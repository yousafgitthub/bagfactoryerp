<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, suppliers: Array, warehouses: Array });

const form = useForm({
    supplier_id: '',
    warehouse_id: '',
    return_date: new Date().toISOString().slice(0, 10),
    notes: '',
    lines: [{ material_id: '', quantity: 1, unit_price: 0, unit_cost: 0 }],
});

const submit = () => form.post(route('purchases.returns.store'));
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="pageTitle" />
        <form class="erp-card max-w-2xl space-y-4 p-6" @submit.prevent="submit">
            <select v-model="form.supplier_id" class="erp-input" required>
                <option value="">Supplier</option>
                <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
            <select v-model="form.warehouse_id" class="erp-input" required>
                <option value="">Warehouse</option>
                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
            </select>
            <input v-model="form.return_date" type="date" class="erp-input" required />
            <div v-for="(line, i) in form.lines" :key="i" class="flex gap-2">
                <input v-model="line.material_id" type="number" class="erp-input" placeholder="Material ID" required />
                <input v-model="line.quantity" type="number" step="0.0001" class="erp-input" placeholder="Qty" required />
                <input v-model="line.unit_price" type="number" step="0.01" class="erp-input" placeholder="Price" required />
            </div>
            <button type="button" class="erp-btn-secondary text-sm" @click="form.lines.push({ material_id: '', quantity: 1, unit_price: 0, unit_cost: 0 })">+ Line</button>
            <button type="submit" class="erp-btn-primary">Save draft</button>
        </form>
    </ErpLayout>
</template>