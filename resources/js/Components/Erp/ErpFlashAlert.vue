<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const success = computed(() => page.props.flash?.success ?? page.props.flash?.status ?? '');
const error = computed(() => page.props.flash?.error ?? '');
</script>

<template>
    <div
        v-if="error"
        class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/50 dark:text-red-200"
        role="alert"
    >
        {{ error }}
    </div>
    <div
        v-else-if="success"
        class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200"
        role="status"
    >
        {{ success }}
    </div>
</template>