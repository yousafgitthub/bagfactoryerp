<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    hint: {
        type: String,
        default: '',
    },
    trend: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <article class="erp-card p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-erp-muted">
            {{ label }}
        </p>
        <p class="mt-2 text-2xl font-semibold tabular-nums text-slate-900 dark:text-slate-50">
            {{ value }}
        </p>
        <p v-if="hint" class="mt-1 text-sm text-erp-muted">
            {{ hint }}
        </p>
        <p v-if="trend" class="mt-2 text-xs font-medium text-emerald-600 dark:text-emerald-400">
            {{ trend }}
        </p>
    </article>
</template>