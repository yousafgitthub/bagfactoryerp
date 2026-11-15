<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    enquiry: Object,
    customers: Array,
    customerQuery: String,
    statuses: Object,
});

const customerQ = ref(props.customerQuery || '');
const form = useForm({
    status: props.enquiry.status,
    internal_notes: props.enquiry.internalNotes || '',
    customer_id: props.enquiry.customerId || '',
});

const searchCustomers = () => {
    router.get(route('website.admin.enquiries.show', props.enquiry.id), { customer_q: customerQ.value }, { preserveState: true, preserveScroll: true });
};

const save = () => form.put(route('website.admin.enquiries.update', props.enquiry.id));
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="enquiry.reference" :description="`${enquiry.type} · ${enquiry.createdAt}`" />
        <div class="grid gap-6 lg:grid-cols-2">
            <section class="erp-card space-y-2 p-6 text-sm">
                <p><span class="font-semibold">Name:</span> {{ enquiry.name }}</p>
                <p><span class="font-semibold">Email:</span> {{ enquiry.email }}</p>
                <p v-if="enquiry.phone"><span class="font-semibold">Phone:</span> {{ enquiry.phone }}</p>
                <p v-if="enquiry.company"><span class="font-semibold">Company:</span> {{ enquiry.company }}</p>
                <p><span class="font-semibold">Subject:</span> {{ enquiry.productLabel }}</p>
                <p v-if="enquiry.message" class="whitespace-pre-line"><span class="font-semibold">Message:</span> {{ enquiry.message }}</p>
                <dl v-if="Object.keys(enquiry.details || {}).length" class="mt-3 space-y-1">
                    <div v-for="(value, key) in enquiry.details" :key="key">
                        <span class="font-semibold">{{ key }}:</span> {{ value }}
                    </div>
                </dl>
                <a v-if="enquiry.artworkHref" :href="enquiry.artworkHref" class="inline-block font-semibold text-erp-primary">Download {{ enquiry.artworkName || 'artwork' }}</a>
            </section>
            <form class="erp-card space-y-4 p-6" @submit.prevent="save">
                <label class="block text-sm font-medium">
                    Status
                    <select v-model="form.status" class="erp-input mt-1">
                        <option v-for="(label, value) in statuses" :key="value" :value="value">{{ label }}</option>
                    </select>
                </label>
                <label class="block text-sm font-medium">
                    Internal notes
                    <textarea v-model="form.internal_notes" rows="5" class="erp-input mt-1" />
                </label>
                <div>
                    <label class="block text-sm font-medium">Link an existing customer</label>
                    <div class="mt-1 flex gap-2">
                        <input v-model="customerQ" class="erp-input" placeholder="Search name or code" @keyup.enter.prevent="searchCustomers" />
                        <button type="button" class="erp-btn-secondary" @click="searchCustomers">Find</button>
                    </div>
                    <select v-model="form.customer_id" class="erp-input mt-2">
                        <option value="">Not linked</option>
                        <option v-for="customer in customers" :key="customer.id" :value="customer.id">{{ customer.code }} — {{ customer.name }}</option>
                    </select>
                </div>
                <button type="submit" class="erp-btn-primary" :disabled="form.processing">Save follow-up</button>
                <p class="text-xs text-erp-muted">
                    <Link :href="route('website.admin.enquiries.index')">Back to enquiries</Link>
                </p>
            </form>
        </div>
    </ErpLayout>
</template>
