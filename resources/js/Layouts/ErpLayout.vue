<script setup>
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ErpBreadcrumbs from '@/Components/Erp/ErpBreadcrumbs.vue';
import ErpSidebar from '@/Components/Erp/ErpSidebar.vue';

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

const user = computed(() => page.props.auth?.user);
const navigation = computed(() => page.props.navigation ?? []);
const notifications = computed(() => page.props.notifications ?? []);

watch(
    () => page.url,
    () => {
        sidebarOpen.value = false;
    },
);
</script>

<template>
    <div class="min-h-screen bg-slate-100 text-slate-900">
        <ErpSidebar
            :navigation="navigation"
            :collapsed="sidebarCollapsed"
            :mobile-open="sidebarOpen"
            :app-name="page.props.appName"
            @toggle-collapsed="sidebarCollapsed = !sidebarCollapsed"
            @close-mobile="sidebarOpen = false"
        />

        <div
            class="flex min-h-screen flex-col transition-[margin] duration-200 lg:ml-64"
            :class="{ 'lg:ml-20': sidebarCollapsed }"
        >
            <header
                class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/95 px-4 shadow-sm backdrop-blur sm:px-6"
            >
                <button
                    type="button"
                    class="inline-flex rounded-md border border-slate-200 p-2 text-slate-600 hover:bg-slate-50 lg:hidden"
                    aria-label="Open navigation"
                    @click="sidebarOpen = true"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="min-w-0 flex-1">
                    <h1 class="truncate text-lg font-semibold text-slate-900">
                        {{ pageTitle }}
                    </h1>
                    <ErpBreadcrumbs v-if="breadcrumbs.length" :items="breadcrumbs" class="mt-0.5 hidden sm:flex" />
                </div>

                <div class="flex items-center gap-2 sm:gap-4">
                    <div class="relative hidden sm:block" aria-label="Notifications">
                        <button
                            type="button"
                            class="relative rounded-md border border-slate-200 p-2 text-slate-600 hover:bg-slate-50"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                                />
                            </svg>
                            <span
                                v-if="notifications.length"
                                class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] font-bold text-white"
                            >
                                {{ notifications.length }}
                            </span>
                        </button>
                    </div>

                    <div class="relative">
                        <details class="group relative">
                            <summary
                                class="flex cursor-pointer list-none items-center gap-2 rounded-md border border-slate-200 px-2 py-1.5 text-sm hover:bg-slate-50 sm:px-3"
                            >
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-800 text-xs font-semibold uppercase text-white"
                                >
                                    {{ user?.name?.charAt(0) ?? '?' }}
                                </span>
                                <span class="hidden max-w-[8rem] truncate font-medium sm:inline">
                                    {{ user?.name }}
                                </span>
                            </summary>
                            <div
                                class="absolute right-0 z-30 mt-2 w-48 rounded-md border border-slate-200 bg-white py-1 shadow-lg"
                            >
                                <div class="border-b border-slate-100 px-3 py-2 text-xs text-slate-500">
                                    {{ user?.email }}
                                </div>
                                <Link
                                    :href="route('profile.edit')"
                                    class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                                >
                                    Profile & password
                                </Link>
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="block w-full px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50"
                                >
                                    Log out
                                </Link>
                            </div>
                        </details>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6">
                <slot />
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