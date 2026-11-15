<script setup>
import ErpEmptyState from '@/Components/Erp/ErpEmptyState.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    enquiries: Object,
    filters: Object,
    statuses: Object,
});

const type = ref(props.filters?.type ?? '');
const status = ref(props.filters?.status ?? '');
const apply = () => router.get(route('website.admin.enquiries.index'), { type: type.value, status: status.value }, { preserveState: true });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Website enquiries"
            help-text="Sales follow-up from the public site. Updating status does not create a sales invoice — convert in Sales when ready."
        />
        <form class="mb-4 flex flex-wrap gap-2" @submit.prevent="apply">
            <select v-model="type" class="erp-input max-w-[12rem]">
                <option value="">All types</option>
                <option value="quote">Quote</option>
                <option value="custom_order">Custom order</option>
                <option value="contact">Contact</option>
            </select>
            <select v-model="status" class="erp-input max-w-[12rem]">
                <option value="">All statuses</option>
                <option v-for="(label, value) in statuses" :key="value" :value="value">{{ label }}</option>
            </select>
            <button type="submit" class="erp-btn-secondary">Filter</button>
        </form>
        <div class="erp-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="erp-table-head">
                    <tr>
                        <th class="px-4 py-3">Reference</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">From</th>
                        <th class="px-4 py-3">Subject</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Received</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in enquiries.data" :key="row.id" class="border-t border-slate-200">
                        <td class="px-4 py-3"><Link :href="row.href" class="font-semibold text-erp-primary">{{ row.reference }}</Link></td>
                        <td class="px-4 py-3">{{ row.type }}</td>
                        <td class="px-4 py-3">{{ row.name }}<br /><span class="text-erp-muted">{{ row.email }}</span></td>
                        <td class="px-4 py-3">{{ row.productLabel }}</td>
                        <td class="px-4 py-3">{{ row.status }}</td>
                        <td class="px-4 py-3">{{ row.createdAt }}</td>
                    </tr>
                    <tr v-if="!enquiries.data?.length">
                        <td colspan="6" class="p-0">
                            <ErpEmptyState title="No enquiries yet." description="New contact and quote forms will appear here." class="border-0 shadow-none" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </ErpLayout>
</template>
