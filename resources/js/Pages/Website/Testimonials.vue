<script setup>
import SitePage from '@/Components/Website/SitePage.vue';

defineProps({
    title: { type: String, required: true },
    testimonials: { type: Array, default: () => [] },
});
</script>

<template>
    <SitePage :title="title">
        <div v-if="testimonials.length" class="grid gap-4 md:grid-cols-2">
            <blockquote v-for="item in testimonials" :key="item.authorName + item.quote" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <p class="text-slate-700">“{{ item.quote }}”</p>
                <footer class="mt-4 text-sm font-bold text-[#0f2744]">
                    {{ item.authorName }}
                    <span v-if="item.authorRole" class="font-medium text-slate-500"> · {{ item.authorRole }}</span>
                </footer>
            </blockquote>
        </div>
        <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-slate-600">
            No testimonials have been published. The section stays empty until the factory adds a real one.
        </div>
    </SitePage>
</template>
