<script setup>
import SitePage from '@/Components/Website/SitePage.vue';

defineProps({
    title: { type: String, required: true },
    body: { type: String, default: null },
    empty: { type: Boolean, default: false },
});
</script>

<template>
    <SitePage :title="title" intro="Who the factory is, in its own words.">
        <article v-if="!empty" class="max-w-3xl whitespace-pre-line text-base leading-8 text-slate-700">{{ body }}</article>
        <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-slate-600">
            About text has not been published yet. Add it in the ERP website settings.
        </div>
    </SitePage>
</template>
