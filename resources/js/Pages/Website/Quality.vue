<script setup>
import SitePage from '@/Components/Website/SitePage.vue';

defineProps({
    title: { type: String, required: true },
    notice: { type: String, required: true },
    steps: { type: Array, default: () => [] },
});
</script>

<template>
    <SitePage :title="title" :intro="notice">
        <p class="mb-6 rounded-2xl bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-950 ring-1 ring-amber-200">
            Sample catalog text. The steps below describe a demonstration quality-control sequence for this website. They are not certifications, customer testimonials, or claims about capacity or results. Replace this page when the factory writes its own process.
        </p>
        <ol class="space-y-4">
            <li v-for="(step, index) in steps" :key="step.title" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-bold uppercase tracking-wide text-[#1d4ed8]">Step {{ index + 1 }}</p>
                <h2 class="mt-1 text-lg font-extrabold text-[#0f2744]">{{ step.title }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ step.body }}</p>
            </li>
        </ol>
    </SitePage>
</template>
