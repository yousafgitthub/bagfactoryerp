<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-slate-900">{{ title }}</h2>
        <p v-if="description" class="mt-1 text-sm text-slate-600">
            {{ description }}
        </p>
    </div>
</template>