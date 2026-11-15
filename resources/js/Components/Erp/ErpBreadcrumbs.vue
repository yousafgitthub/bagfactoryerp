<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    items: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <nav class="flex flex-wrap items-center gap-1 text-xs text-slate-500 dark:text-slate-400" aria-label="Breadcrumb">
        <template v-for="(item, index) in items" :key="index">
            <span v-if="index > 0" aria-hidden="true">/</span>
            <Link
                v-if="item.href"
                :href="item.href"
                class="hover:text-slate-800 hover:underline dark:hover:text-slate-100"
            >
                {{ item.label }}
            </Link>
            <span v-else class="font-medium text-slate-700 dark:text-slate-200">{{ item.label }}</span>
        </template>
    </nav>
</template>