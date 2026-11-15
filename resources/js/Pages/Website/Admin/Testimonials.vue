<script setup>
import ErpEmptyState from '@/Components/Erp/ErpEmptyState.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    testimonials: Array,
});

const createForm = useForm({
    author_name: '',
    author_role: '',
    quote: '',
    is_visible: 1,
    sort_order: 0,
});

const edits = reactive({});
props.testimonials.forEach((item) => {
    edits[item.id] = useForm({
        author_name: item.author_name,
        author_role: item.author_role || '',
        quote: item.quote,
        is_visible: item.is_visible ? 1 : 0,
        sort_order: item.sort_order || 0,
    });
});

const create = () => createForm.post(route('website.admin.testimonials.store'));
const save = (item) => edits[item.id].put(route('website.admin.testimonials.update', item.id));
const remove = (item) => {
    if (confirm('Remove this testimonial?')) {
        router.delete(route('website.admin.testimonials.destroy', item.id));
    }
};
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Testimonials"
            help-text="Only add real customer quotes. When the list is empty the public testimonials section stays hidden."
        />
        <form class="erp-card mb-6 space-y-3 p-6" @submit.prevent="create">
            <label class="block text-sm font-medium">Name<input v-model="createForm.author_name" class="erp-input mt-1" required /></label>
            <label class="block text-sm font-medium">Role<input v-model="createForm.author_role" class="erp-input mt-1" /></label>
            <label class="block text-sm font-medium">Quote<textarea v-model="createForm.quote" rows="3" class="erp-input mt-1" required /></label>
            <label class="flex items-center gap-2 text-sm"><input v-model="createForm.is_visible" type="checkbox" :true-value="1" :false-value="0" /> Visible</label>
            <button class="erp-btn-primary" :disabled="createForm.processing">Add testimonial</button>
        </form>
        <ErpEmptyState v-if="!testimonials.length" title="No testimonials yet." description="Add verified quotes from customers you are allowed to name." />
        <form v-for="item in testimonials" :key="item.id" class="erp-card mb-4 space-y-3 p-6" @submit.prevent="save(item)">
            <input v-model="edits[item.id].author_name" class="erp-input" />
            <input v-model="edits[item.id].author_role" class="erp-input" />
            <textarea v-model="edits[item.id].quote" rows="3" class="erp-input" />
            <label class="flex items-center gap-2 text-sm"><input v-model="edits[item.id].is_visible" type="checkbox" :true-value="1" :false-value="0" /> Visible</label>
            <div class="flex gap-2">
                <button class="erp-btn-primary" :disabled="edits[item.id].processing">Save</button>
                <button type="button" class="erp-btn-danger" @click="remove(item)">Remove</button>
            </div>
        </form>
    </ErpLayout>
</template>
