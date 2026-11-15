<script setup>
import CatalogArt from '@/Components/Website/CatalogArt.vue';
import SitePage from '@/Components/Website/SitePage.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    activeCategory: { type: String, default: null },
    categories: { type: Array, default: () => [] },
    category: { type: Object, default: null },
    products: { type: Array, default: () => [] },
    search: { type: String, default: '' },
});

const page = usePage();
const showSample = computed(() => page.props.website?.showSampleNotice);
const query = ref(props.search || '');

const submitSearch = () => {
    const href = props.activeCategory ? `/products/${props.activeCategory}` : '/products';
    router.get(href, { q: query.value }, { preserveState: true, replace: true });
};

const artFor = (slug) => {
    if (slug === 'printed-bags') return 'printed';
    if (slug === 'custom-bags') return 'custom';
    if (slug === 'dana-granules') return 'dana';

    return 'shopping';
};
</script>

<template>
    <SitePage :title="title" intro="Published products only. Internal prices and stock quantities are not shown.">
        <p v-if="showSample" class="mb-6 text-sm text-slate-500">Sample catalog descriptions. The factory can replace this text. It is not a verified company claim.</p>
        <form class="mb-6 flex max-w-xl gap-2" @submit.prevent="submitSearch">
            <input v-model="query" type="search" name="q" placeholder="Search published products" class="w-full rounded-full border border-slate-200 px-4 py-2 text-sm" />
            <button type="submit" class="rounded-full bg-[#0f2744] px-4 py-2 text-sm font-bold text-white">Search</button>
        </form>

        <div class="mb-8 flex flex-wrap gap-2">
            <Link href="/products" class="rounded-full px-4 py-2 text-sm font-bold" :class="!activeCategory ? 'bg-[#1d4ed8] text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200'">All</Link>
            <Link
                v-for="item in categories"
                :key="item.slug"
                :href="item.href"
                class="rounded-full px-4 py-2 text-sm font-bold"
                :class="activeCategory === item.slug ? 'bg-[#1d4ed8] text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200'"
            >
                {{ item.name }}
            </Link>
        </div>

        <div v-if="category" class="mb-8 max-w-3xl rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <p class="text-slate-700">{{ category.summary }}</p>
            <p v-if="category.body" class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-600">{{ category.body }}</p>
        </div>

        <div v-if="products.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <article v-for="product in products" :key="product.category + product.slug" class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="h-40 bg-slate-50">
                    <img v-if="product.imageUrl" :src="product.imageUrl" :alt="product.name" class="h-full w-full object-cover" />
                    <CatalogArt v-else :variant="artFor(product.category)" />
                </div>
                <div class="p-5">
                    <p v-if="product.categoryName" class="text-xs font-bold uppercase tracking-wide text-[#0f766e]">{{ product.categoryName }}</p>
                    <h2 class="mt-1 text-lg font-extrabold text-[#0f2744]">{{ product.name }}</h2>
                    <dl class="mt-3 space-y-1 text-sm text-slate-600">
                        <div v-if="product.typeLabel"><span class="font-semibold text-slate-500">Type </span>{{ product.typeLabel }}</div>
                        <div v-if="product.size"><span class="font-semibold text-slate-500">Size </span>{{ product.size }}</div>
                        <div v-if="product.color"><span class="font-semibold text-slate-500">Color </span>{{ product.color }}</div>
                        <div v-if="product.material"><span class="font-semibold text-slate-500">Material </span>{{ product.material }}</div>
                        <div v-if="product.thickness"><span class="font-semibold text-slate-500">Thickness </span>{{ product.thickness }}</div>
                    </dl>
                    <p v-if="product.summary" class="mt-2 text-sm leading-6 text-slate-600">{{ product.summary }}</p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <Link :href="product.href" class="inline-flex text-sm font-bold text-[#1d4ed8]">View details →</Link>
                        <Link :href="product.quoteHref" class="inline-flex rounded-full bg-[#1d4ed8] px-3 py-1.5 text-sm font-bold text-white">Request Quote</Link>
                    </div>
                </div>
            </article>
        </div>
        <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-slate-600">
            No products are published in this category yet. The factory can mark items public from the ERP website listings.
        </div>
    </SitePage>
</template>
