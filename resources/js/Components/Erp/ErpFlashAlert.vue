<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const message = computed(() => page.props.flash?.status ?? '');
</script>

<template>
    <div
        v-if="message"
        class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200"
        role="status"
    >
        {{ message }}
    </div>
</template>