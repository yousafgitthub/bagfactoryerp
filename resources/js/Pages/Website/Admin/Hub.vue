<script setup>
import ErpModuleIntro from '@/Components/Erp/ErpModuleIntro.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    newEnquiries: Number,
    links: Array,
});
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Website"
            :help-text="`${newEnquiries} new enquiries waiting for follow-up. Changes here update the public site — they do not post to sales or inventory.`"
        />
        <ErpModuleIntro
            title="Public website"
            :steps="[
                'Review enquiries and link customers when you convert a lead.',
                'Publish bag products and dana materials customers can browse.',
                'Refresh gallery, categories, and company copy from settings.',
            ]"
        />
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Link v-for="item in links" :key="item.href" :href="item.href" class="erp-card block p-5 transition hover:ring-2 hover:ring-erp-primary/30">
                <h3 class="text-base font-semibold">{{ item.label }}</h3>
                <p class="mt-1 text-sm text-erp-muted">{{ item.description }}</p>
            </Link>
        </div>
    </ErpLayout>
</template>
