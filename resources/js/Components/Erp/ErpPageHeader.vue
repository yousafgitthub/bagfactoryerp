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
    helpText: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-50">{{ title }}</h2>
            <p v-if="description || helpText" class="mt-1 text-sm text-erp-muted">
                {{ description || helpText }}
            </p>
        </div>
        <div v-if="$slots.actions" class="flex shrink-0 flex-wrap gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>