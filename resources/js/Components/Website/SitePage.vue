<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    title: { type: String, required: true },
    intro: { type: String, default: '' },
});
</script>

<template>
    <Head :title="title" />
    <PublicLayout>
        <section class="bg-gradient-to-br from-[#0b3d91] via-[#1d4ed8] to-[#0f766e] text-white">
            <div class="mx-auto max-w-6xl px-4 py-14">
                <h1 class="text-4xl font-extrabold tracking-tight">{{ title }}</h1>
                <p v-if="intro" class="mt-3 max-w-2xl text-blue-100">{{ intro }}</p>
            </div>
        </section>
        <div class="mx-auto max-w-6xl px-4 py-12">
            <slot />
        </div>
    </PublicLayout>
</template>
