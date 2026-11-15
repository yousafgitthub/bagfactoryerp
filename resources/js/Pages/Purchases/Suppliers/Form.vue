<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, supplier: Object });

const form = useForm({
    code: props.supplier?.code ?? '',
    name: props.supplier?.name ?? '',
    contact_person: props.supplier?.contact_person ?? '',
    phone: props.supplier?.phone ?? '',
    address: props.supplier?.address ?? '',
    opening_balance: props.supplier?.opening_balance ?? 0,
    notes: props.supplier?.notes ?? '',
    status: props.supplier?.status ?? 'active',
});

const submit = () => {
    if (props.supplier) {
        form.put(route('purchases.suppliers.update', props.supplier.id));
    } else {
        form.post(route('purchases.suppliers.store'));
    }
};
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="pageTitle" />
        <form class="erp-card max-w-2xl space-y-4 p-6" @submit.prevent="submit">
            <div v-if="!supplier" class="grid gap-2">
                <label class="text-sm font-medium">Code (auto if empty)</label>
                <input v-model="form.code" class="erp-input" />
            </div>
            <div class="grid gap-2">
                <label class="text-sm font-medium">Name</label>
                <input v-model="form.name" class="erp-input" required />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <label class="text-sm font-medium">Contact person</label>
                    <input v-model="form.contact_person" class="erp-input" />
                </div>
                <div class="grid gap-2">
                    <label class="text-sm font-medium">Phone</label>
                    <input v-model="form.phone" class="erp-input" />
                </div>
            </div>
            <div class="grid gap-2">
                <label class="text-sm font-medium">Address</label>
                <textarea v-model="form.address" class="erp-input" rows="2" />
            </div>
            <div v-if="!supplier" class="grid gap-2">
                <label class="text-sm font-medium">Opening balance (payable)</label>
                <input v-model="form.opening_balance" type="number" step="0.01" class="erp-input" />
            </div>
            <div v-if="supplier" class="grid gap-2">
                <label class="text-sm font-medium">Status</label>
                <select v-model="form.status" class="erp-input">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="grid gap-2">
                <label class="text-sm font-medium">Notes</label>
                <textarea v-model="form.notes" class="erp-input" rows="2" />
            </div>
            <button type="submit" class="erp-btn-primary" :disabled="form.processing">Save</button>
        </form>
    </ErpLayout>
</template>