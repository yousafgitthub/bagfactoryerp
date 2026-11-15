<script setup>
import { Link, usePage } from '@inertiajs/vue3';

defineProps({
    navigation: {
        type: Array,
        default: () => [],
    },
    collapsed: Boolean,
    mobileOpen: Boolean,
    appName: {
        type: String,
        default: 'Bag Factory ERP',
    },
});

defineEmits(['toggle-collapsed', 'close-mobile']);

const page = usePage();

const isActive = (item) => {
    if (!item.route) {
        return false;
    }

    return route().current(item.route);
};
</script>

<template>
    <aside
        class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-slate-200 bg-slate-900 text-slate-100 shadow-xl transition-transform duration-200 lg:translate-x-0"
        :class="[
            mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            collapsed ? 'lg:w-20' : 'lg:w-64',
        ]"
    >
        <div class="flex h-16 items-center justify-between gap-2 border-b border-slate-800 px-4">
            <Link :href="route('dashboard')" class="truncate text-sm font-bold tracking-wide">
                <span v-if="!collapsed" class="text-white">{{ appName }}</span>
                <span v-else class="text-white">BF</span>
            </Link>
            <button
                type="button"
                class="hidden rounded p-1 text-slate-400 hover:bg-slate-800 hover:text-white lg:inline-flex"
                aria-label="Toggle sidebar width"
                @click="$emit('toggle-collapsed')"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
            </button>
            <button
                type="button"
                class="rounded p-1 text-slate-400 hover:text-white lg:hidden"
                aria-label="Close navigation"
                @click="$emit('close-mobile')"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto p-2">
            <Link
                v-for="item in navigation"
                :key="item.key"
                :href="item.href"
                class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition"
                :class="
                    isActive(item)
                        ? 'bg-slate-800 text-white'
                        : 'text-slate-300 hover:bg-slate-800/70 hover:text-white'
                "
                @click="$emit('close-mobile')"
            >
                <span
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-slate-800 text-xs font-bold uppercase"
                >
                    {{ item.label.slice(0, 2) }}
                </span>
                <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
            </Link>
        </nav>

        <div v-if="page.props.auth?.user && !collapsed" class="border-t border-slate-800 p-3 text-xs text-slate-400">
            Roles: {{ page.props.auth.user.roles?.join(', ') || '—' }}
        </div>
    </aside>
</template>