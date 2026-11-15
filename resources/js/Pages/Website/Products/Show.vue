<script setup>
import CatalogArt from '@/Components/Website/CatalogArt.vue';
import SitePage from '@/Components/Website/SitePage.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    title: { type: String, required: true },
    category: { type: Object, required: true },
    product: { type: Object, required: true },
    quoteHref: { type: String, required: true },
});

const artFor = (slug) => {
    if (slug === 'printed-bags') return 'printed';
    if (slug === 'custom-bags') return 'custom';
    if (slug === 'dana-granules') return 'dana';

    return 'shopping';
};
</script>

<template>
    <SitePage :title="title">
        <p class="mb-6 text-sm text-slate-500">
            <Link href="/products" class="font-semibold text-[#1d4ed8]">Products</Link>
            <span> / </span>
            <Link :href="category.href" class="font-semibold text-[#1d4ed8]">{{ category.name }}</Link>
        </p>
        <div class="grid gap-8 lg:grid-cols-2">
            <div class="overflow-hidden rounded-3xl bg-white ring-1 ring-slate-200">
                <img v-if="product.imageUrl" :src="product.imageUrl" :alt="product.name" class="h-full w-full object-cover" />
                <CatalogArt v-else :variant="artFor(product.category)" />
            </div>
            <div>
                <p v-if="product.summary" class="text-lg text-slate-700">{{ product.summary }}</p>
                <p v-if="product.description" class="mt-4 whitespace-pre-line leading-7 text-slate-600">{{ product.description }}</p>
                <dl v-if="product.specs?.length" class="mt-6 divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white">
                    <div v-for="spec in product.specs" :key="spec.label" class="grid grid-cols-2 gap-3 px-4 py-3 text-sm">
                        <dt class="font-semibold text-slate-500">{{ spec.label }}</dt>
                        <dd class="text-slate-800">{{ spec.value }}</dd>
                    </div>
                </dl>
                <Link :href="quoteHref" class="mt-6 inline-flex rounded-full bg-[#1d4ed8] px-5 py-3 text-sm font-bold text-white">Request a quote</Link>
            </div>
        </div>
    </SitePage>
</template>
