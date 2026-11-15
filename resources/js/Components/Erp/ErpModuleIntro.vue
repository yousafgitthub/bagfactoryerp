<script setup>
defineProps({
    title: {
        type: String,
        default: 'How this works',
    },
    helpText: {
        type: String,
        default: '',
    },
    steps: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <aside
        class="erp-card mb-4 border-l-4 border-[#5468f5]/50 bg-slate-50/80 p-4 text-sm dark:bg-slate-900/40"
        role="note"
    >
        <h3 class="font-semibold text-slate-900 dark:text-slate-50">{{ title }}</h3>
        <ol v-if="steps.length" class="mt-2 list-decimal space-y-1 pl-5 text-erp-muted">
            <li v-for="(step, index) in steps" :key="index">{{ step }}</li>
        </ol>
        <p v-else-if="helpText" class="mt-1 text-erp-muted">{{ helpText }}</p>
    </aside>
</template>