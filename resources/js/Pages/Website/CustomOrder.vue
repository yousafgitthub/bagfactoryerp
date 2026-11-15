<script setup>
import EnquiryForm from '@/Components/Website/EnquiryForm.vue';
import SitePage from '@/Components/Website/SitePage.vue';

defineProps({
    title: { type: String, required: true },
    formToken: { type: String, required: true },
    bagTypes: { type: Array, default: () => [] },
    printOptions: { type: Array, default: () => [] },
});
</script>

<template>
    <SitePage :title="title" intro="Tell the factory what to make. This does not create an invoice.">
        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <EnquiryForm
                mode="custom"
                :form-token="formToken"
                submit-href="/custom-orders"
                :bag-types="bagTypes"
                :print-options="printOptions"
            />
        </div>
    </SitePage>
</template>
