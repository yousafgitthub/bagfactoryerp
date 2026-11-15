<script setup>
import ErpEmptyState from '@/Components/Erp/ErpEmptyState.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    bags: Object,
    materials: Object,
    filters: Object,
    canManage: Boolean,
});

const q = ref(props.filters?.q ?? '');
const search = () => router.get(route('website.admin.listings.index'), { q: q.value }, { preserveState: true });
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Public listings"
            help-text="Mark items public to show them on the website. ERP price and stock stay unchanged until you sell through Sales."
        />
        <form class="mb-6 flex gap-2" @submit.prevent="search">
            <input v-model="q" type="search" class="erp-input max-w-xs" placeholder="Search code or name" />
            <button type="submit" class="erp-btn-secondary">Search</button>
        </form>
        <section class="mb-8">
            <h2 class="mb-3 text-lg font-semibold">Bag products</h2>
            <div class="erp-card overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="erp-table-head">
                        <tr>
                            <th class="px-4 py-3">Code</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Public</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in bags.data" :key="row.id" class="border-t border-slate-200">
                            <td class="px-4 py-3">{{ row.code }}</td>
                            <td class="px-4 py-3">{{ row.name }}</td>
                            <td class="px-4 py-3">{{ row.status }}</td>
                            <td class="px-4 py-3">{{ row.isPublic ? row.category : 'Hidden' }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link v-if="canManage" :href="row.href" class="font-semibold text-erp-primary">Edit</Link>
                            </td>
                        </tr>
                        <tr v-if="!bags.data?.length">
                            <td colspan="5" class="p-0">
                                <ErpEmptyState title="No bag products in catalog." description="Create bag products in production, then enable public listing here." class="border-0 shadow-none" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-3 flex gap-3 text-sm">
                <Link v-if="bags.prev_page_url" :href="bags.prev_page_url">Previous bags</Link>
                <Link v-if="bags.next_page_url" :href="bags.next_page_url">Next bags</Link>
            </div>
        </section>
        <section>
            <h2 class="mb-3 text-lg font-semibold">Materials (dana and granules)</h2>
            <div class="erp-card overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="erp-table-head">
                        <tr>
                            <th class="px-4 py-3">Code</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Public</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in materials.data" :key="row.id" class="border-t border-slate-200">
                            <td class="px-4 py-3">{{ row.code }}</td>
                            <td class="px-4 py-3">{{ row.name }}</td>
                            <td class="px-4 py-3">{{ row.status }}</td>
                            <td class="px-4 py-3">{{ row.isPublic ? row.category : 'Hidden' }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link v-if="canManage" :href="row.href" class="font-semibold text-erp-primary">Edit</Link>
                            </td>
                        </tr>
                        <tr v-if="!materials.data?.length">
                            <td colspan="5" class="p-0">
                                <ErpEmptyState title="No materials in catalog." description="Publish dana or granules customers can request quotes for." class="border-0 shadow-none" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-3 flex gap-3 text-sm">
                <Link v-if="materials.prev_page_url" :href="materials.prev_page_url">Previous materials</Link>
                <Link v-if="materials.next_page_url" :href="materials.next_page_url">Next materials</Link>
            </div>
        </section>
    </ErpLayout>
</template>
