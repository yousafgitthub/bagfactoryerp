<script setup>
defineProps({
    message: {
        type: String,
        default: 'Loading…',
    },
});
</script>

<template>
    <div class="erp-card flex items-center gap-3 px-6 py-10" role="status" aria-live="polite">
        <span
            class="inline-block h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-erp-primary"
            aria-hidden="true"
        />
        <span class="text-sm text-erp-muted">{{ message }}</span>
    </div>
</template>