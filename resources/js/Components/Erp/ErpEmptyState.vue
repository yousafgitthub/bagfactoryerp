<script setup>
defineProps({
    title: {
        type: String,
        default: 'Nothing here yet',
    },
    description: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div
        class="erp-card flex flex-col items-center justify-center border-dashed px-6 py-12 text-center"
        role="status"
    >
        <div
            class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300"
            aria-hidden="true"
        >
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M20 13V7a2 2 0 00-2-2h-3.5M4 7v12m0 0h9m-9 0h3m12-8h-5"
                />
            </svg>
        </div>
        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-50">
            {{ title }}
        </h3>
        <p v-if="description" class="mt-2 max-w-md text-sm text-erp-muted">
            {{ description }}
        </p>
        <div v-if="$slots.actions" class="mt-4 flex flex-wrap justify-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>