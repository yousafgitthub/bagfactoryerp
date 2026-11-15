<script setup>
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ErpIcon from '@/Components/Erp/ErpIcon.vue';
import {
    ERP_MODULE_SECTION,
    ERP_NAV_ICONS,
    ERP_NAV_SECTIONS,
    navChildrenFor,
} from '@/support/erpNavStructure';

const props = defineProps({
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
const openModules = ref({});

const brandTitle = computed(() => props.appName.replace(/\s*ERP\s*$/i, '').trim() || props.appName);
const brandMark = computed(() => (/ERP/i.test(props.appName) ? 'ERP' : ''));

const pathname = (value) => {
    const raw = String(value ?? '').trim();

    if (!raw) {
        return '';
    }

    const withoutOrigin = raw.split('#')[0].replace(/^[a-z][a-z0-9+.-]*:\/\/[^/?#]+/i, '');
    const path = withoutOrigin.split('?')[0].replace(/\/+$/, '');

    return path || '/';
};

const currentPath = computed(() => pathname(page.url));

const childrenFor = (moduleKey) => navChildrenFor(moduleKey);

const moduleTargets = (item) => {
    const paths = [pathname(item.href)];

    for (const child of childrenFor(item.key)) {
        paths.push(pathname(child.href));
    }

    return paths.filter(Boolean);
};

const activeKey = computed(() => {
    let bestKey = null;
    let bestLength = -1;

    for (const item of props.navigation) {
        for (const target of moduleTargets(item)) {
            const matches =
                target === '/'
                    ? currentPath.value === '/'
                    : currentPath.value === target || currentPath.value.startsWith(`${target}/`);

            if (matches && target.length > bestLength) {
                bestKey = item.key;
                bestLength = target.length;
            }
        }
    }

    return bestKey;
});

const isCurrent = (name) => {
    page.url;

    if (!name || !route().has(name)) {
        return false;
    }

    if (route().current(name)) {
        return true;
    }

    if (name.endsWith('.index')) {
        return route().current(`${name.replace(/\.index$/, '')}.*`);
    }

    return false;
};

const itemActive = (item) => item.key === activeKey.value;

const moduleOpen = (key) => openModules.value[key] === true;

const toggleModule = (key) => {
    openModules.value = { ...openModules.value, [key]: !moduleOpen(key) };
};

const ensureOpenWhenActive = (key) => {
    if (key === activeKey.value && childrenFor(key).length > 1) {
        openModules.value = { ...openModules.value, [key]: true };
    }
};

watch(
    activeKey,
    (key) => {
        if (key) {
            ensureOpenWhenActive(key);
        }
    },
    { immediate: true },
);

const groupedNavigation = computed(() => {
    const buckets = Object.fromEntries(ERP_NAV_SECTIONS.map((section) => [section.key, []]));

    for (const item of props.navigation) {
        const sectionKey = ERP_MODULE_SECTION[item.key] ?? 'operations';
        buckets[sectionKey]?.push(item);
    }

    return ERP_NAV_SECTIONS.map((section) => ({
        ...section,
        items: buckets[section.key] ?? [],
    })).filter((section) => section.items.length > 0);
});

const linkClass = (active) =>
    active
        ? 'bg-[#5468f5] text-white shadow-lg shadow-indigo-950/40'
        : 'text-slate-200 hover:bg-white/10 hover:text-white';
</script>

<template>
    <aside
        class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-gradient-to-b from-[#0c1b4d] via-[#12275f] to-[#16356f] text-slate-100 shadow-2xl transition-transform duration-200 lg:translate-x-0"
        :class="[
            mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            collapsed ? 'lg:w-20' : 'lg:w-64',
        ]"
    >
        <div class="flex h-[4.5rem] shrink-0 items-center justify-between gap-2 px-4">
            <Link :href="route('dashboard')" class="flex min-w-0 items-center gap-3" @click="$emit('close-mobile')">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-400 to-indigo-500 text-white shadow-lg">
                    <ErpIcon name="bag" class="h-5 w-5" />
                </span>
                <span v-if="!collapsed" class="min-w-0">
                    <span class="block truncate text-sm font-bold leading-tight text-white">{{ brandTitle }}</span>
                    <span v-if="brandMark" class="block text-[10px] font-semibold tracking-[0.22em] text-sky-200">{{ brandMark }}</span>
                </span>
            </Link>
            <button
                type="button"
                class="hidden rounded-lg p-1 text-slate-300 hover:bg-white/10 hover:text-white lg:inline-flex"
                aria-label="Toggle sidebar width"
                @click="$emit('toggle-collapsed')"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
            </button>
            <button
                type="button"
                class="rounded-lg p-1 text-slate-300 hover:bg-white/10 hover:text-white lg:hidden"
                aria-label="Close navigation"
                @click="$emit('close-mobile')"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-2">
            <template v-for="section in groupedNavigation" :key="section.key">
                <p
                    v-if="section.label && !collapsed"
                    class="mb-1 mt-3 px-3 text-[10px] font-semibold uppercase tracking-[0.18em] text-sky-200/70 first:mt-0"
                >
                    {{ section.label }}
                </p>
                <div class="space-y-1">
                    <template v-for="item in section.items" :key="item.key">
                        <div v-if="childrenFor(item.key).length > 1 && !collapsed">
                            <button
                                type="button"
                                class="flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition"
                                :class="linkClass(itemActive(item))"
                                :aria-expanded="moduleOpen(item.key)"
                                @click="toggleModule(item.key)"
                            >
                                <ErpIcon :name="ERP_NAV_ICONS[item.key] || 'box'" class="h-5 w-5 shrink-0" />
                                <span class="flex-1 truncate text-left">{{ item.label }}</span>
                                <ErpIcon name="chevron" class="h-4 w-4 shrink-0 transition" :class="moduleOpen(item.key) ? 'rotate-90' : ''" />
                            </button>
                            <div v-show="moduleOpen(item.key)" class="ml-5 mt-1 space-y-1 border-l border-white/15 py-1 pl-3">
                                <Link
                                    v-for="child in childrenFor(item.key)"
                                    :key="child.routeName"
                                    :href="child.href"
                                    class="block rounded-xl px-3 py-1.5 text-sm transition"
                                    :class="
                                        isCurrent(child.routeName)
                                            ? 'bg-white/15 font-semibold text-white'
                                            : 'text-slate-300 hover:bg-white/10 hover:text-white'
                                    "
                                    @click="$emit('close-mobile')"
                                >
                                    {{ child.label }}
                                </Link>
                            </div>
                        </div>
                        <Link
                            v-else
                            :href="item.href"
                            class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition"
                            :class="linkClass(itemActive(item))"
                            :title="collapsed ? item.label : undefined"
                            @click="$emit('close-mobile')"
                        >
                            <ErpIcon :name="ERP_NAV_ICONS[item.key] || 'box'" class="h-5 w-5 shrink-0" />
                            <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
                        </Link>
                    </template>
                </div>
            </template>
        </nav>

        <div v-if="!collapsed" class="relative mx-3 mb-4 mt-2 shrink-0 overflow-hidden rounded-2xl bg-[#0a1844]/70 px-4 py-4">
            <svg class="pointer-events-none absolute inset-x-0 bottom-0 h-16 w-full opacity-50" viewBox="0 0 220 70" preserveAspectRatio="xMidYMax slice" aria-hidden="true">
                <path d="M0 70V48h18V28h22v20h28V18h34v30h26V34h30v14h62V70Z" fill="#7dd3fc" />
                <rect x="86" y="26" width="6" height="6" fill="#fde68a" />
                <rect x="98" y="26" width="6" height="6" fill="#fde68a" />
                <rect x="110" y="26" width="6" height="6" fill="#fde68a" />
                <path d="M150 48h18l6 10h-8l-2 8h-10z" fill="#f9a8d4" />
            </svg>
            <p class="relative text-sm font-semibold leading-snug text-white">
                Quality Bags.<br />
                Built for Your Business.
            </p>
        </div>
    </aside>
</template>