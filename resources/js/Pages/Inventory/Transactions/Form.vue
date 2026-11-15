<script setup>
import ErpFormSection from '@/Components/Erp/ErpFormSection.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    type: String,
    transactionTypes: Object,
    lookups: Object,
});

const form = useForm({
    type: props.type,
    transaction_date: new Date().toISOString().slice(0, 10),
    warehouse_id: '',
    from_warehouse_id: '',
    to_warehouse_id: '',
    notes: '',
    adjustment_reason: '',
    adjustment_direction: 'increase',
    items: [{ material_id: '', material_variant_id: '', quantity: '', unit_cost: '' }],
});

const isTransfer = computed(() => form.type === 'warehouse_transfer');
const isAdjustment = computed(() => form.type === 'adjustment');

const addLine = () => {
    form.items.push({ material_id: '', material_variant_id: '', quantity: '', unit_cost: '' });
};

const submit = () => {
    form.post(route('inventory.transactions.store'));
};
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="New stock movement" />

        <form class="erp-card max-w-4xl p-6" @submit.prevent="submit">
            <ErpFormSection title="Header">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="erp-label">
                        Type
                        <select v-model="form.type" class="erp-input">
                            <option v-for="(label, key) in transactionTypes" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </label>
                    <label class="erp-label">
                        Date
                        <input v-model="form.transaction_date" type="date" class="erp-input" required />
                    </label>
                    <template v-if="isTransfer">
                        <label class="erp-label">
                            From warehouse
                            <select v-model="form.from_warehouse_id" class="erp-input" required>
                                <option value="" disabled>Select</option>
                                <option v-for="w in lookups.warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                            </select>
                        </label>
                        <label class="erp-label">
                            To warehouse
                            <select v-model="form.to_warehouse_id" class="erp-input" required>
                                <option value="" disabled>Select</option>
                                <option v-for="w in lookups.warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                            </select>
                        </label>
                    </template>
                    <label v-else class="erp-label sm:col-span-2">
                        Warehouse
                        <select v-model="form.warehouse_id" class="erp-input" required>
                            <option value="" disabled>Select</option>
                            <option v-for="w in lookups.warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                        </select>
                    </label>
                    <template v-if="isAdjustment">
                        <label class="erp-label">
                            Direction
                            <select v-model="form.adjustment_direction" class="erp-input">
                                <option value="increase">Increase</option>
                                <option value="decrease">Decrease</option>
                            </select>
                        </label>
                        <label class="erp-label">
                            Reason
                            <input v-model="form.adjustment_reason" class="erp-input" required />
                        </label>
                    </template>
                </div>
            </ErpFormSection>

            <ErpFormSection title="Lines" class="mt-6">
                <div v-for="(line, index) in form.items" :key="index" class="mb-3 grid gap-2 sm:grid-cols-4">
                    <select v-model="line.material_id" class="erp-input" required>
                        <option value="" disabled>Material</option>
                        <option v-for="m in lookups.materials" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                    <input v-model="line.quantity" type="number" step="0.0001" class="erp-input" placeholder="Qty" required />
                    <input v-model="line.unit_cost" type="number" step="0.0001" class="erp-input" placeholder="Unit cost" />
                </div>
                <button type="button" class="erp-btn-secondary text-sm" @click="addLine">+ Line</button>
            </ErpFormSection>

            <div class="mt-6">
                <button type="submit" class="erp-btn-primary" :disabled="form.processing">Save draft</button>
            </div>
        </form>
    </ErpLayout>
</template>