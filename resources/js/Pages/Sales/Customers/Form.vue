<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, customer: Object });

const form = useForm({
    code: props.customer?.code ?? '',
    name: props.customer?.name ?? '',
    contact_person: props.customer?.contact_person ?? '',
    phone: props.customer?.phone ?? '',
    address: props.customer?.address ?? '',
    opening_balance: props.customer?.opening_balance ?? 0,
    notes: props.customer?.notes ?? '',
    status: props.customer?.status ?? 'active',
});

const submit = () => {
    if (props.customer) {
        form.put(route('sales.customers.update', props.customer.id));
    } else {
        form.post(route('sales.customers.store'));
    }
};
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="pageTitle" />
        <form class="erp-card max-w-2xl space-y-4 p-6" @submit.prevent="submit">
            <div v-if="!customer" class="grid gap-4 sm:grid-cols-2">
                <div><label class="text-sm font-medium">Code (optional)</label><input v-model="form.code" class="erp-input w-full" /></div>
                <div><label class="text-sm font-medium">Opening balance</label><input v-model="form.opening_balance" type="number" step="0.01" class="erp-input w-full" /></div>
            </div>
            <div><label class="text-sm font-medium">Name</label><input v-model="form.name" class="erp-input w-full" required /></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="text-sm font-medium">Contact</label><input v-model="form.contact_person" class="erp-input w-full" /></div>
                <div><label class="text-sm font-medium">Phone</label><input v-model="form.phone" class="erp-input w-full" /></div>
            </div>
            <div><label class="text-sm font-medium">Address</label><textarea v-model="form.address" class="erp-input w-full" rows="2" /></div>
            <div v-if="customer"><label class="text-sm font-medium">Status</label><select v-model="form.status" class="erp-input w-full"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
            <div><label class="text-sm font-medium">Notes</label><textarea v-model="form.notes" class="erp-input w-full" rows="2" /></div>
            <button type="submit" class="erp-btn-primary" :disabled="form.processing">Save</button>
        </form>
    </ErpLayout>
</template>