<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, customers: Array, warehouses: Array, materials: Array, conditions: Object });

const form = useForm({
    customer_id: '',
    warehouse_id: '',
    return_date: new Date().toISOString().slice(0, 10),
    notes: '',
    lines: [{ material_id: '', quantity: 1, condition: 'saleable', unit_price: 0, tax_rate: 0, inspection_notes: '' }],
});

const submit = () => form.post(route('sales.returns.store'));
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="New sales return" />
        <form class="erp-card space-y-4 p-6" @submit.prevent="submit">
            <select v-model="form.customer_id" class="erp-input w-full" required>
                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <select v-model="form.warehouse_id" class="erp-input w-full" required>
                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
            </select>
            <div v-for="(line, i) in form.lines" :key="i" class="grid gap-2 sm:grid-cols-4">
                <select v-model="line.material_id" class="erp-input" required>
                    <option v-for="m in materials" :key="m.id" :value="m.id">{{ m.code }}</option>
                </select>
                <input v-model="line.quantity" type="number" class="erp-input" required />
                <select v-model="line.condition" class="erp-input">
                    <option v-for="(label, key) in conditions" :key="key" :value="key">{{ label }}</option>
                </select>
                <input v-model="line.unit_price" type="number" class="erp-input" required />
            </div>
            <button type="submit" class="erp-btn-primary">Save draft</button>
        </form>
    </ErpLayout>
</template>