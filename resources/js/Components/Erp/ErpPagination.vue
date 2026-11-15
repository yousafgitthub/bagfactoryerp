<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    links: {
        type: Array,
        default: () => [],
    },
    meta: {
        type: Object,
        default: null,
    },
});

const summary = computed(() => {
    if (!props.meta) {
        return '';
    }

    return `Page ${props.meta.current_page} of ${props.meta.last_page}`;
});
</script>

<template>
    <nav
        v-if="links.length"
        class="flex flex-col gap-2 border-t border-erp-border px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
        aria-label="Pagination"
    >
        <p v-if="summary" class="text-xs text-erp-muted">
            {{ summary }}
        </p>
        <ul class="flex flex-wrap gap-1">
            <li v-for="(link, index) in links" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="inline-flex min-w-[2.25rem] items-center justify-center rounded-md border px-2 py-1 text-xs font-medium transition"
                    :class="
                        link.active
                            ? 'border-erp-primary bg-erp-primary text-white'
                            : 'border-erp-border bg-white text-slate-700 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700'
                    "
                    :preserve-scroll="true"
                    v-html="link.label"
                />
                <span
                    v-else
                    class="inline-flex min-w-[2.25rem] items-center justify-center rounded-md border border-transparent px-2 py-1 text-xs text-erp-muted"
                    v-html="link.label"
                />
            </li>
        </ul>
    </nav>
</template>