<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        default: 'neutral',
    },
    label: {
        type: String,
        default: '',
    },
});

const styles = {
    success: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    warning: 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-300',
    danger: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
    info: 'bg-sky-100 text-sky-900 dark:bg-sky-950 dark:text-sky-300',
    draft: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
    posted: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    neutral: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
};

const badgeClass = computed(() => {
    const key = props.status?.toLowerCase() ?? 'neutral';

    return styles[key] ?? styles.neutral;
});

const text = computed(() => props.label || props.status);
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize"
        :class="badgeClass"
    >
        {{ text }}
    </span>
</template>