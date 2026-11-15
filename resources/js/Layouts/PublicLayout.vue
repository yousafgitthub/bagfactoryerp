<script setup>
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const site = computed(() => page.props.website ?? {});
const flash = computed(() => page.props.flash?.status ?? '');
const menuOpen = ref(false);
const productsOpen = ref(false);

const pathOf = (href) => {
    try {
        return new URL(href, window.location.origin).pathname;
    } catch {
        return href;
    }
};

const currentPath = computed(() => page.url.split('?')[0]);

const active = (item) => {
    if (item.match === 'home') {
        return currentPath.value === '/';
    }

    if (item.match === 'products') {
        return currentPath.value === '/products' || currentPath.value.startsWith('/products/');
    }

    return currentPath.value === pathOf(item.href);
};

watch(
    () => page.url,
    () => {
        menuOpen.value = false;
        productsOpen.value = false;
    },
);
</script>

<template>
    <div class="min-h-screen bg-[#f4f7fb] font-display text-slate-900">
        <div class="bg-[#071a4a] text-sm text-white">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-4 gap-y-1 px-4 py-2">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                    <a v-if="site.phone" :href="`tel:${site.phone}`" class="inline-flex items-center gap-2 hover:text-sky-200">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M6 4h3l2 5-2 1a12 12 0 0 0 5 5l1-2 5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 4 6a2 2 0 0 1 2-2z" />
                        </svg>
                        {{ site.phone }}
                    </a>
                    <a v-if="site.email" :href="`mailto:${site.email}`" class="inline-flex items-center gap-2 hover:text-sky-200">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="14" rx="2" />
                            <path d="m4 7 8 6 8-6" />
                        </svg>
                        {{ site.email }}
                    </a>
                </div>
                <div class="flex items-center gap-3">
                    <span v-if="site.tagline" class="hidden text-blue-100 sm:inline">{{ site.tagline }}</span>
                    <a v-if="site.facebookUrl" :href="site.facebookUrl" class="hover:text-sky-200" rel="noopener noreferrer" target="_blank">Facebook</a>
                    <a v-if="site.linkedinUrl" :href="site.linkedinUrl" class="hover:text-sky-200" rel="noopener noreferrer" target="_blank">LinkedIn</a>
                </div>
            </div>
        </div>

        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3">
                <Link href="/" class="flex min-w-0 items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#1d4ed8] text-white shadow-lg shadow-blue-200">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M6 8h12l-1 12H7z" />
                            <path d="M9 8V7a3 3 0 0 1 6 0v1" />
                        </svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-lg font-extrabold leading-none text-[#0f2744]">{{ site.companyName || 'Bag Factory' }}</span>
                        <span class="mt-1 block text-[11px] font-semibold uppercase tracking-[0.16em] text-[#0f766e]">Plastic packaging</span>
                    </span>
                </Link>

                <nav class="ml-auto hidden items-center gap-1 lg:flex" aria-label="Primary">
                    <template v-for="item in site.nav || []" :key="item.href">
                        <div v-if="item.match === 'products'" class="relative">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 rounded-full px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-blue-50 hover:text-[#1d4ed8]"
                                :class="active(item) ? 'text-[#1d4ed8]' : ''"
                                :aria-expanded="productsOpen"
                                @click="productsOpen = !productsOpen"
                            >
                                Products
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6" /></svg>
                            </button>
                            <div v-show="productsOpen" class="absolute left-0 top-full z-20 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
                                <Link :href="item.href" class="block rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-blue-50">All products</Link>
                                <Link
                                    v-for="category in site.categories || []"
                                    :key="category.slug"
                                    :href="category.href"
                                    class="block rounded-xl px-3 py-2 text-sm text-slate-700 hover:bg-blue-50"
                                >
                                    {{ category.name }}
                                </Link>
                            </div>
                        </div>
                        <Link
                            v-else
                            :href="item.href"
                            class="rounded-full px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-blue-50 hover:text-[#1d4ed8]"
                            :class="active(item) ? 'text-[#1d4ed8]' : ''"
                        >
                            {{ item.label }}
                        </Link>
                    </template>
                </nav>

                <Link
                    :href="site.quoteHref || '/request-a-quote'"
                    class="ml-auto inline-flex rounded-full bg-[#1d4ed8] px-3 py-2 text-sm font-bold text-white shadow-lg shadow-blue-200 hover:bg-[#1e40af] sm:px-4 lg:ml-3"
                >
                    <span class="sm:hidden">Quote</span>
                    <span class="hidden sm:inline">Request a Quote</span>
                </Link>
                <button
                    type="button"
                    class="inline-flex rounded-xl border border-slate-200 p-2 text-slate-700 lg:hidden"
                    aria-label="Open menu"
                    @click="menuOpen = !menuOpen"
                >
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </button>
            </div>
            <div v-show="menuOpen" class="border-t border-slate-200 bg-white px-4 py-3 lg:hidden">
                <div class="flex flex-col gap-1">
                    <template v-for="item in site.nav || []" :key="`m-${item.href}`">
                        <Link :href="item.href" class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-800 hover:bg-blue-50">{{ item.label }}</Link>
                        <template v-if="item.match === 'products'">
                            <Link
                                v-for="category in site.categories || []"
                                :key="`m-${category.slug}`"
                                :href="category.href"
                                class="rounded-xl px-6 py-2 text-sm text-slate-600 hover:bg-blue-50"
                            >
                                {{ category.name }}
                            </Link>
                        </template>
                    </template>
                    <Link :href="site.quoteHref || '/request-a-quote'" class="mt-2 rounded-full bg-[#1d4ed8] px-4 py-2 text-center text-sm font-bold text-white">
                        Request a Quote
                    </Link>
                </div>
            </div>
        </header>

        <div v-if="flash" class="bg-emerald-50 text-emerald-900">
            <p class="mx-auto max-w-6xl px-4 py-3 text-sm font-medium">{{ flash }}</p>
        </div>

        <main>
            <slot />
        </main>

        <footer class="mt-8 bg-[#071a4a] text-blue-100">
            <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="text-lg font-extrabold text-white">{{ site.companyName || 'Bag Factory' }}</p>
                    <p class="mt-2 text-sm leading-6">Plastic shopping bags, printed bags, custom packaging, and dana granules. Published product details are maintained in the factory ERP.</p>
                </div>
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-white">Pages</p>
                    <div class="mt-3 flex flex-col gap-2 text-sm">
                        <Link v-for="item in site.nav || []" :key="`f-${item.href}`" :href="item.href" class="hover:text-white">{{ item.label }}</Link>
                        <Link :href="site.quoteHref || '/request-a-quote'" class="hover:text-white">Request a Quote</Link>
                        <Link href="/custom-orders" class="hover:text-white">Custom Order</Link>
                    </div>
                </div>
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-white">Contact</p>
                    <div class="mt-3 space-y-2 text-sm">
                        <p v-if="site.phone">{{ site.phone }}</p>
                        <p v-if="site.email">{{ site.email }}</p>
                        <p v-if="site.address" class="whitespace-pre-line">{{ site.address }}</p>
                        <a v-if="site.mapUrl" :href="site.mapUrl" class="inline-block font-semibold text-white underline" rel="noopener noreferrer" target="_blank">Open map</a>
                        <p v-if="!site.phone && !site.email && !site.address">Contact details have not been published yet.</p>
                        <a :href="site.loginHref || '/login'" class="inline-block pt-2 text-xs text-blue-200 hover:text-white">Staff sign in</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
