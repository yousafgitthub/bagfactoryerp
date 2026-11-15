<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    categories: Array,
});

const forms = reactive({});

props.categories.forEach((category) => {
    forms[category.id] = useForm({
        name: category.name,
        card_title: category.card_title || '',
        summary: category.summary || '',
        body: category.body || '',
        is_sample: category.is_sample ? 1 : 0,
        is_visible: category.is_visible ? 1 : 0,
    });
});

const save = (category) => {
    forms[category.id].put(route('website.admin.categories.update', category.id));
};
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Public categories"
            help-text="Card copy visitors see on the website. Slugs stay fixed so public URLs do not change."
        />
        <div class="space-y-6">
            <form v-for="category in categories" :key="category.id" class="erp-card space-y-3 p-6" @submit.prevent="save(category)">
                <p class="text-xs font-semibold uppercase tracking-wide text-erp-muted">{{ category.slug }}</p>
                <label class="block text-sm font-medium">Name<input v-model="forms[category.id].name" class="erp-input mt-1" /></label>
                <label class="block text-sm font-medium">Card line<input v-model="forms[category.id].card_title" class="erp-input mt-1" /></label>
                <label class="block text-sm font-medium">Summary<textarea v-model="forms[category.id].summary" rows="2" class="erp-input mt-1" /></label>
                <label class="block text-sm font-medium">Body<textarea v-model="forms[category.id].body" rows="4" class="erp-input mt-1" /></label>
                <label class="flex items-center gap-2 text-sm"><input v-model="forms[category.id].is_sample" type="checkbox" :true-value="1" :false-value="0" /> Mark as sample catalog text</label>
                <label class="flex items-center gap-2 text-sm"><input v-model="forms[category.id].is_visible" type="checkbox" :true-value="1" :false-value="0" /> Visible on the public site</label>
                <button type="submit" class="erp-btn-primary" :disabled="forms[category.id].processing">Save</button>
            </form>
        </div>
    </ErpLayout>
</template>
