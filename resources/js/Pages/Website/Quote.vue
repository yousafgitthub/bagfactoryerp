<script setup>
import EnquiryForm from '@/Components/Website/EnquiryForm.vue';
import SitePage from '@/Components/Website/SitePage.vue';

defineProps({
    title: { type: String, required: true },
    formToken: { type: String, required: true },
    categories: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    prefill: { type: Object, default: () => ({}) },
});
</script>

<template>
    <SitePage :title="title" intro="Ask for a price. Published pages do not show internal prices.">
        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <EnquiryForm
                mode="quote"
                :form-token="formToken"
                submit-href="/request-a-quote"
                :categories="categories"
                :products="products"
                :prefill="prefill"
            />
        </div>
    </SitePage>
</template>
