<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import ErpBreadcrumbs from '@/Components/Erp/ErpBreadcrumbs.vue';
import ErpContentContainer from '@/Components/Erp/ErpContentContainer.vue';
import ErpFlashAlert from '@/Components/Erp/ErpFlashAlert.vue';
import ErpIcon from '@/Components/Erp/ErpIcon.vue';
import ErpSidebar from '@/Components/Erp/ErpSidebar.vue';
import { useErpSearch } from '@/composables/useErpSearch';
import { useErpTheme } from '@/composables/useErpTheme';

defineProps({
    pageTitle: {
        type: String,
        default: 'Bag Factory ERP',
    },
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const sidebarOpen = ref(false);
const sidebarCollapsed = ref(false);
const notesOpen = ref(false);
const { isDark, setDark } = useErpTheme();
const { query } = useErpSearch();

const user = computed(() => page.props.auth?.user);
const navigation = computed(() => page.props.navigation ?? []);
const notifications = computed(() => page.props.notifications ?? []);
const onDashboard = computed(() => {
    page.url;

    return route().current('dashboard');
});

const initials = computed(() => {
    const parts = (user.value?.name ?? '').trim().split(/\s+/).filter(Boolean);

    if (!parts.length) {
        return '?';
    }

    return parts
        .slice(0, 2)
        .map((part) => part.charAt(0))
        .join('')
        .toUpperCase();
});

const noteLabel = (note) => {
    if (note == null) {
        return '';
    }

    if (typeof note === 'string') {
        return note;
    }

    return note.title || note.message || note.body || '';
};

watch(
    () => page.url,
    () => {
        sidebarOpen.value = false;
        notesOpen.value = false;
    },
);

const submitSearch = () => {
    if (onDashboard.value) {
        return;
    }

    const term = query.value.trim();

    if (!term) {
        return;
    }

    const keys = navigation.value.map((item) => item.key);

    if (keys.includes('inventory') && route().has('inventory.materials.index')) {
        router.get(route('inventory.materials.index'), { search: term });

        return;
    }

    if (keys.includes('employees') && route().has('hr.employees.index')) {
        router.get(route('hr.employees.index'), { search: term });
    }
};
</script>

<template>
    <div class="erp-shell min-h-screen">
        <ErpSidebar
            :navigation="navigation"
            :collapsed="sidebarCollapsed"
            :mobile-open="sidebarOpen"
            :app-name="page.props.appName"
            @toggle-collapsed="sidebarCollapsed = !sidebarCollapsed"
            @close-mobile="sidebarOpen = false"
        />

        <div
            class="flex min-h-screen min-w-0 flex-col transition-[margin] duration-200 lg:ml-64"
            :class="{ 'lg:ml-20': sidebarCollapsed }"
        >
            <header class="sticky top-0 z-20 border-b border-white/60 bg-[rgb(var(--erp-bg)/0.9)] px-4 py-3 backdrop-blur dark:border-slate-800 dark:bg-slate-950/90 sm:px-6">
                <h1 class="sr-only">{{ pageTitle }}</h1>
                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                    <button
                        type="button"
                        class="inline-flex rounded-xl border border-slate-200 bg-white p-2 text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 lg:hidden"
                        aria-label="Open navigation"
                        @click="sidebarOpen = true"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div class="order-2 ml-auto flex shrink-0 items-center gap-1.5 sm:order-3 sm:ml-0 sm:gap-2">
                        <div class="flex items-center rounded-full bg-white p-1 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-700">
                            <button
                                type="button"
                                class="rounded-full p-1.5 transition"
                                :class="!isDark ? 'bg-amber-100 text-amber-500' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'"
                                aria-label="Light theme"
                                :aria-pressed="!isDark"
                                @click="setDark(false)"
                            >
                                <ErpIcon name="sun" class="h-4 w-4" />
                            </button>
                            <button
                                type="button"
                                class="rounded-full p-1.5 transition"
                                :class="isDark ? 'bg-slate-700 text-indigo-200' : 'text-slate-400 hover:text-slate-600'"
                                aria-label="Dark theme"
                                :aria-pressed="isDark"
                                @click="setDark(true)"
                            >
                                <ErpIcon name="moon" class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="relative">
                            <button
                                type="button"
                                class="relative rounded-full bg-white p-2 text-slate-600 shadow-sm ring-1 ring-slate-200 hover:text-indigo-600 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700"
                                aria-label="Notifications"
                                :aria-expanded="notesOpen"
                                @click="notesOpen = !notesOpen"
                            >
                                <ErpIcon name="bell" class="h-4 w-4" />
                                <span
                                    v-if="notifications.length"
                                    class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white"
                                >
                                    {{ notifications.length }}
                                </span>
                            </button>
                            <div
                                v-if="notesOpen"
                                class="absolute right-0 z-30 mt-2 w-72 rounded-2xl border border-slate-200 bg-white p-4 text-sm shadow-xl dark:border-slate-700 dark:bg-slate-900"
                            >
                                <p class="font-semibold text-slate-900 dark:text-slate-50">Notifications</p>
                                <p v-if="!notifications.length" class="mt-2 text-erp-muted">No notifications.</p>
                                <ul v-else class="mt-2 space-y-2">
                                    <li v-for="(note, index) in notifications" :key="index" class="text-slate-700 dark:text-slate-200">
                                        {{ noteLabel(note) }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <details class="group relative">
                            <summary class="flex cursor-pointer list-none items-center gap-2 rounded-full bg-white py-1 pl-1 pr-2 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:ring-slate-700 dark:hover:bg-slate-800 sm:pr-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#1e2a5a] text-xs font-semibold text-white">
                                    {{ initials }}
                                </span>
                                <span class="hidden max-w-[9rem] truncate text-sm font-medium text-slate-800 dark:text-slate-100 sm:inline">
                                    {{ user?.name }}
                                </span>
                            </summary>
                            <div class="absolute right-0 z-30 mt-2 w-56 rounded-2xl border border-slate-200 bg-white py-1 shadow-xl dark:border-slate-700 dark:bg-slate-900">
                                <div class="border-b border-slate-100 px-3 py-2 text-xs text-erp-muted dark:border-slate-800">
                                    <p class="truncate">{{ user?.email }}</p>
                                    <p v-if="user?.roles?.length" class="mt-1 truncate">Roles: {{ user.roles.join(', ') }}</p>
                                </div>
                                <Link
                                    :href="route('profile.edit')"
                                    class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                                >
                                    Profile & password
                                </Link>
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="block w-full px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40"
                                >
                                    Log out
                                </Link>
                            </div>
                        </details>
                    </div>

                    <form class="relative order-3 w-full min-w-0 sm:order-2 sm:w-auto sm:flex-1" @submit.prevent="submitSearch">
                        <ErpIcon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="query"
                            type="search"
                            class="w-full min-w-0 rounded-full border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-700 shadow-sm outline-none ring-indigo-200 placeholder:text-slate-400 focus:ring-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                            placeholder="Search here..."
                            :aria-label="onDashboard ? 'Filter dashboard lists' : 'Search records'"
                        />
                    </form>
                </div>
                <ErpBreadcrumbs v-if="breadcrumbs.length > 1" :items="breadcrumbs" class="mt-2" />
            </header>

            <main class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">
                <ErpContentContainer>
                    <ErpFlashAlert />
                    <slot />
                </ErpContentContainer>
            </main>
        </div>

        <Transition
            enter-active-class="transition-opacity duration-200"
            leave-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="sidebarOpen"
                class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"
                @click="sidebarOpen = false"
            />
        </Transition>
    </div>
</template>
