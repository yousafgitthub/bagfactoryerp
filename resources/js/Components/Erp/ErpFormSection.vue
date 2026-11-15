<script setup>
defineProps({
    title: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <header v-if="title" class="mb-4 border-b border-slate-100 pb-3">
            <h3 class="text-base font-semibold text-slate-900">{{ title }}</h3>
            <p v-if="description" class="mt-1 text-sm text-slate-600">{{ description }}</p>
        </header>
        <slot />
    </section>
</template>