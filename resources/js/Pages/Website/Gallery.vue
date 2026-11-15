<script setup>
import SitePage from '@/Components/Website/SitePage.vue';

defineProps({
    title: { type: String, required: true },
    items: { type: Array, default: () => [] },
});
</script>

<template>
    <SitePage :title="title" intro="Images uploaded by the factory. Nothing is pulled from a photo library.">
        <div v-if="items.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <figure v-for="item in items" :key="item.imageUrl" class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                <img :src="item.imageUrl" :alt="item.title || 'Gallery image'" class="h-56 w-full object-cover" />
                <figcaption v-if="item.title || item.caption" class="p-4">
                    <p v-if="item.title" class="font-bold text-[#0f2744]">{{ item.title }}</p>
                    <p v-if="item.caption" class="mt-1 text-sm text-slate-600">{{ item.caption }}</p>
                </figcaption>
            </figure>
        </div>
        <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-slate-600">
            Gallery images will appear here after they are uploaded in the ERP. This slot is empty on purpose.
        </div>
    </SitePage>
</template>
