<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    items: Array,
});

const createForm = useForm({
    title: '',
    caption: '',
    sort_order: 0,
    image: null,
});

const edits = reactive({});
props.items.forEach((item) => {
    edits[item.id] = useForm({
        title: item.title || '',
        caption: item.caption || '',
        sort_order: item.sortOrder || 0,
        is_visible: item.isVisible ? 1 : 0,
    });
});

const onFile = (event) => {
    createForm.image = event.target.files?.[0] ?? null;
};

const upload = () => {
    createForm.post(route('website.admin.gallery.store'), { forceFormData: true });
};

const save = (item) => edits[item.id].put(route('website.admin.gallery.update', item.id));
const remove = (item) => {
    if (confirm('Remove this gallery image?')) {
        router.delete(route('website.admin.gallery.destroy', item.id));
    }
};
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Gallery" description="JPG, PNG, or WEBP only. Files stay outside the public web root." />
        <form class="erp-card mb-6 space-y-3 p-6" @submit.prevent="upload">
            <label class="block text-sm font-medium">Title<input v-model="createForm.title" class="erp-input mt-1" /></label>
            <label class="block text-sm font-medium">Caption<textarea v-model="createForm.caption" rows="2" class="erp-input mt-1" /></label>
            <label class="block text-sm font-medium">Image<input type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="mt-1 block text-sm" @change="onFile" /></label>
            <p v-if="createForm.errors.image" class="text-sm text-red-600">{{ createForm.errors.image }}</p>
            <button class="erp-btn-primary" :disabled="createForm.processing">Upload</button>
        </form>
        <div v-if="!items.length" class="rounded-xl border border-dashed border-slate-300 p-6 text-sm text-erp-muted">No gallery images yet.</div>
        <div class="space-y-4">
            <form v-for="item in items" :key="item.id" class="erp-card grid gap-4 p-4 md:grid-cols-[10rem_1fr]" @submit.prevent="save(item)">
                <img :src="item.imageUrl" alt="" class="h-32 w-full rounded-lg object-cover" />
                <div class="space-y-2">
                    <input v-model="edits[item.id].title" class="erp-input" />
                    <textarea v-model="edits[item.id].caption" rows="2" class="erp-input" />
                    <label class="flex items-center gap-2 text-sm"><input v-model="edits[item.id].is_visible" type="checkbox" :true-value="1" :false-value="0" /> Visible</label>
                    <div class="flex gap-2">
                        <button class="erp-btn-primary" :disabled="edits[item.id].processing">Save</button>
                        <button type="button" class="erp-btn-danger" @click="remove(item)">Remove</button>
                    </div>
                </div>
            </form>
        </div>
    </ErpLayout>
</template>
