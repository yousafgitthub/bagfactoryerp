<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    listing: Object,
    categories: Array,
});

const form = useForm({
    is_public: props.listing.isPublic ? 1 : 0,
    public_slug: props.listing.publicSlug || '',
    public_category: props.listing.publicCategory || '',
    public_summary: props.listing.publicSummary || '',
    public_description: props.listing.publicDescription || '',
    public_sort: props.listing.publicSort || 0,
    remove_image: 0,
    image: null,
});

const onFile = (event) => {
    form.image = event.target.files?.[0] ?? null;
};

const submit = () => {
    form.post(route('website.admin.listings.update', { source: props.listing.source, id: props.listing.id }), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="listing.name" :description="`${listing.code} · master status ${listing.status}. Hidden on the site until status is active and this listing is published.`" />
        <form class="erp-card max-w-3xl space-y-4 p-6" @submit.prevent="submit">
            <label class="block text-sm font-medium">
                Visibility
                <select v-model="form.is_public" class="erp-input mt-1">
                    <option :value="1">Published</option>
                    <option :value="0">Hidden</option>
                </select>
            </label>
            <label class="block text-sm font-medium">
                Public category
                <select v-model="form.public_category" class="erp-input mt-1">
                    <option value="">Not set</option>
                    <option v-for="category in categories" :key="category.value" :value="category.value">{{ category.label }}</option>
                </select>
                <span v-if="form.errors.public_category" class="text-sm text-red-600">{{ form.errors.public_category }}</span>
            </label>
            <label class="block text-sm font-medium">
                Public URL slug
                <input v-model="form.public_slug" class="erp-input mt-1" placeholder="classic-loop-bag" />
                <span v-if="form.errors.public_slug" class="text-sm text-red-600">{{ form.errors.public_slug }}</span>
            </label>
            <label class="block text-sm font-medium">
                Public summary
                <textarea v-model="form.public_summary" rows="3" class="erp-input mt-1" />
            </label>
            <label class="block text-sm font-medium">
                Public description
                <textarea v-model="form.public_description" rows="6" class="erp-input mt-1" />
            </label>
            <label class="block text-sm font-medium">
                Sort
                <input v-model="form.public_sort" type="number" min="0" class="erp-input mt-1 max-w-[8rem]" />
            </label>
            <div v-if="listing.imageUrl">
                <img :src="listing.imageUrl" alt="" class="h-32 rounded-lg object-cover" />
                <label class="mt-2 flex items-center gap-2 text-sm">
                    <input v-model="form.remove_image" type="checkbox" :true-value="1" :false-value="0" />
                    Remove image
                </label>
            </div>
            <label class="block text-sm font-medium">
                Image (JPG, PNG, or WEBP, up to 5 MB)
                <input type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="mt-1 block text-sm" @change="onFile" />
                <span v-if="form.errors.image" class="text-sm text-red-600">{{ form.errors.image }}</span>
            </label>
            <button type="submit" class="erp-btn-primary" :disabled="form.processing">Save public listing</button>
        </form>
    </ErpLayout>
</template>
