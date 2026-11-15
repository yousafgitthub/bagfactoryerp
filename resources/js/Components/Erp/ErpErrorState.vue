<script setup>
defineProps({
    title: {
        type: String,
        default: 'Something went wrong',
    },
    message: {
        type: String,
        default: '',
    },
    showRetry: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['retry']);
</script>

<template>
    <div class="erp-card border-red-200 bg-red-50 px-6 py-8 dark:border-red-900 dark:bg-red-950/40" role="alert">
        <h3 class="text-base font-semibold text-red-900 dark:text-red-200">
            {{ title }}
        </h3>
        <p v-if="message" class="mt-2 text-sm text-red-800 dark:text-red-300">
            {{ message }}
        </p>
        <button
            v-if="showRetry"
            type="button"
            class="erp-btn-secondary mt-4"
            @click="$emit('retry')"
        >
            Try again
        </button>
        <slot />
    </div>
</template>