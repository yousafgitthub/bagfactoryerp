<script setup>
import EnquiryForm from '@/Components/Website/EnquiryForm.vue';
import SitePage from '@/Components/Website/SitePage.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    title: { type: String, required: true },
    formToken: { type: String, required: true },
});

const site = computed(() => usePage().props.website ?? {});
const hasContact = computed(() => site.value.phone || site.value.email || site.value.address || site.value.whatsappUrl || site.value.mapUrl);
</script>

<template>
    <SitePage :title="title" intro="Send a message to the factory.">
        <div class="grid gap-8 lg:grid-cols-[0.8fr_1.2fr]">
            <aside class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-extrabold text-[#0f2744]">Published contact details</h2>
                <div v-if="hasContact" class="mt-4 space-y-3 text-sm text-slate-700">
                    <p v-if="site.phone"><span class="font-semibold">Phone:</span> {{ site.phone }}</p>
                    <p v-if="site.email"><span class="font-semibold">Email:</span> {{ site.email }}</p>
                    <p v-if="site.address" class="whitespace-pre-line"><span class="font-semibold">Address:</span> {{ site.address }}</p>
                    <a v-if="site.mapUrl" :href="site.mapUrl" class="inline-block font-bold text-[#1d4ed8]" rel="noopener noreferrer" target="_blank">Open map</a>
                    <a v-if="site.whatsappUrl" :href="site.whatsappUrl" class="inline-flex rounded-full bg-[#0f766e] px-4 py-2 font-bold text-white" rel="noopener noreferrer" target="_blank">WhatsApp</a>
                </div>
                <p v-else class="mt-4 rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                    Phone, email, address, and map have not been configured. The form still reaches the ERP.
                </p>
            </aside>
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <EnquiryForm mode="contact" :form-token="formToken" submit-href="/contact" />
            </div>
        </div>
    </SitePage>
</template>
