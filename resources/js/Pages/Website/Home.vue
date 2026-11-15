<script setup>
import CatalogArt from '@/Components/Website/CatalogArt.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    heroLines: { type: Array, default: () => [] },
    heroSubtitle: { type: String, default: null },
    features: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    whyChooseUs: { type: Array, default: () => [] },
    testimonials: { type: Array, default: () => [] },
    productsHref: { type: String, required: true },
    customOrderHref: { type: String, required: true },
    aboutHref: { type: String, required: true },
});

const page = usePage();
const site = computed(() => page.props.website ?? {});

const artFor = (slug) => {
    if (slug === 'printed-bags') return 'printed';
    if (slug === 'custom-bags') return 'custom';
    if (slug === 'dana-granules') return 'dana';

    return 'shopping';
};
</script>

<template>
    <Head title="Home">
        <meta name="description" content="Plastic shopping bags, printed bags, custom packaging, and dana granules." head-key="description" />
    </Head>
    <PublicLayout>
        <section class="relative overflow-hidden bg-gradient-to-br from-white via-[#eef4ff] to-[#e6fffb]">
            <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-12 lg:grid-cols-2 lg:py-16">
                <div>
                    <h1 class="text-4xl font-extrabold leading-tight tracking-tight text-[#0f2744] sm:text-5xl">
                        <span v-for="(line, index) in heroLines" :key="line" class="block" :class="index === 1 ? 'text-[#1d4ed8]' : ''">{{ line }}</span>
                    </h1>
                    <p v-if="heroSubtitle" class="mt-4 max-w-xl text-base leading-7 text-slate-600">{{ heroSubtitle }}</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <Link :href="productsHref" class="inline-flex items-center rounded-full bg-[#1d4ed8] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-200 hover:bg-[#1e40af]">
                            Our Products →
                        </Link>
                        <a
                            v-if="site.whatsappUrl"
                            :href="site.whatsappUrl"
                            class="inline-flex items-center rounded-full border border-[#0f766e] bg-white px-5 py-3 text-sm font-bold text-[#0f766e] hover:bg-teal-50"
                            rel="noopener noreferrer"
                            target="_blank"
                        >
                            Chat on WhatsApp
                        </a>
                    </div>
                </div>
                <div class="relative">
                    <CatalogArt variant="hero" />
                    <div class="absolute right-3 top-3 flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-amber-200 to-amber-500 text-center text-[11px] font-extrabold uppercase leading-tight text-[#5b3b00] shadow-lg sm:right-6 sm:top-6">
                        Premium<br />Quality
                    </div>
                </div>
            </div>
        </section>

        <section v-if="features.length" class="-mt-4 px-4">
            <div class="mx-auto grid max-w-6xl gap-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-xl shadow-slate-200/60 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="feature in features" :key="feature" class="flex items-center gap-3 rounded-2xl px-3 py-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-[#1d4ed8]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m5 12 5 5L20 7" /></svg>
                    </span>
                    <span class="text-sm font-bold text-[#0f2744]">{{ feature }}</span>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-4 py-16">
            <div class="mb-8 text-center">
                <h2 class="text-3xl font-extrabold text-[#0f2744]">Our Products</h2>
                <p v-if="site.showSampleNotice" class="mx-auto mt-2 max-w-2xl text-sm text-slate-500">
                    Sample catalog descriptions. The factory can replace this text. It is not a verified company claim.
                </p>
            </div>
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <article v-for="category in categories" :key="category.slug" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-lg shadow-slate-200/70">
                    <div class="h-40">
                        <CatalogArt :variant="artFor(category.slug)" />
                    </div>
                    <div class="p-5">
                        <h3 class="text-lg font-extrabold text-[#0f2744]">{{ category.name }}</h3>
                        <p v-if="category.cardTitle" class="mt-1 text-sm font-semibold text-[#0f766e]">{{ category.cardTitle }}</p>
                        <Link :href="category.href" class="mt-4 inline-flex rounded-full bg-[#1d4ed8] px-4 py-2 text-sm font-bold text-white hover:bg-[#1e40af]">
                            View Details →
                        </Link>
                    </div>
                </article>
            </div>
        </section>

        <section class="bg-white">
            <div class="mx-auto grid max-w-6xl items-stretch gap-6 px-4 py-16 lg:grid-cols-[0.9fr_1.2fr_0.9fr]">
                <div>
                    <h2 class="text-3xl font-extrabold text-[#0f2744]">Why Choose Us?</h2>
                    <ul class="mt-6 space-y-4">
                        <li v-for="reason in whyChooseUs" :key="reason.title">
                            <p class="flex items-start gap-2 font-bold text-[#0f2744]">
                                <span class="mt-0.5 text-[#1d4ed8]">✓</span>
                                {{ reason.title }}
                            </p>
                            <p v-if="reason.body" class="ml-6 mt-1 text-sm leading-6 text-slate-600">{{ reason.body }}</p>
                        </li>
                    </ul>
                    <Link :href="aboutHref" class="mt-6 inline-flex rounded-full bg-[#1d4ed8] px-5 py-3 text-sm font-bold text-white hover:bg-[#1e40af]">
                        Learn More →
                    </Link>
                </div>
                <div class="min-h-[240px] overflow-hidden rounded-3xl">
                    <CatalogArt variant="factory" />
                </div>
                <div class="flex flex-col justify-between rounded-3xl bg-gradient-to-br from-[#1d4ed8] to-[#0f766e] p-6 text-white shadow-xl">
                    <div>
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-white/15">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M4 13a8 8 0 0 1 16 0" />
                                <rect x="3" y="13" width="4" height="7" rx="1" />
                                <rect x="17" y="13" width="4" height="7" rx="1" />
                                <path d="M12 8v2" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-extrabold">Need a Custom Order?</h3>
                        <p class="mt-2 text-sm leading-6 text-blue-100">Tell the factory the bag type, size, thickness, color, print, and quantity. Prices are quoted back to you. They are not listed here.</p>
                    </div>
                    <Link :href="customOrderHref" class="mt-6 inline-flex w-fit rounded-full bg-white px-5 py-3 text-sm font-bold text-[#1d4ed8]">
                        Request a Quote →
                    </Link>
                </div>
            </div>
        </section>

        <section v-if="testimonials.length" class="mx-auto max-w-6xl px-4 py-16">
            <h2 class="text-3xl font-extrabold text-[#0f2744]">Testimonials</h2>
            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <blockquote v-for="item in testimonials" :key="item.authorName + item.quote" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-slate-700">“{{ item.quote }}”</p>
                    <footer class="mt-4 text-sm font-bold text-[#0f2744]">
                        {{ item.authorName }}
                        <span v-if="item.authorRole" class="font-medium text-slate-500"> · {{ item.authorRole }}</span>
                    </footer>
                </blockquote>
            </div>
        </section>
    </PublicLayout>
</template>
