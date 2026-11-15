<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    settings: Object,
    canManage: Boolean,
});

const reasons = (props.settings.why_choose_us || []).map((item) => ({
    title: item.title || '',
    body: item.body || '',
}));

while (reasons.length < 4) {
    reasons.push({ title: '', body: '' });
}

const form = useForm({
    company_name: props.settings.company_name || 'Bag Factory',
    tagline: props.settings.tagline || '',
    hero_title: props.settings.hero_title || '',
    hero_subtitle: props.settings.hero_subtitle || '',
    phone: props.settings.phone || '',
    email: props.settings.email || '',
    whatsapp: props.settings.whatsapp || '',
    address: props.settings.address || '',
    map_url: props.settings.map_url || '',
    facebook_url: props.settings.facebook_url || '',
    linkedin_url: props.settings.linkedin_url || '',
    about: props.settings.about || '',
    manufacturing: props.settings.manufacturing || '',
    why_choose_us: reasons,
    feature_strip: [...(props.settings.feature_strip || []), '', '', '', ''].slice(0, 4),
    show_sample_catalog_notice: props.settings.show_sample_catalog_notice ? 1 : 0,
});

const save = () => form.put(route('website.admin.settings.update'));
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Website settings"
            help-text="Contact and hero copy on the public site. Leave fields blank when unknown — do not enter placeholder numbers."
        />
        <form class="erp-card max-w-3xl space-y-4 p-6" @submit.prevent="save">
            <fieldset :disabled="!canManage" class="space-y-4">
                <label class="block text-sm font-medium">Company name<input v-model="form.company_name" class="erp-input mt-1" /></label>
                <label class="block text-sm font-medium">Top-bar tagline<input v-model="form.tagline" class="erp-input mt-1" /></label>
                <label class="block text-sm font-medium">Hero title (one line per row)<textarea v-model="form.hero_title" rows="2" class="erp-input mt-1" /></label>
                <label class="block text-sm font-medium">Hero subtitle<textarea v-model="form.hero_subtitle" rows="2" class="erp-input mt-1" /></label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm font-medium">Phone<input v-model="form.phone" class="erp-input mt-1" /></label>
                    <label class="block text-sm font-medium">Email<input v-model="form.email" type="email" class="erp-input mt-1" /></label>
                    <label class="block text-sm font-medium">WhatsApp number<input v-model="form.whatsapp" class="erp-input mt-1" /></label>
                    <label class="block text-sm font-medium">Map URL (https)<input v-model="form.map_url" class="erp-input mt-1" /></label>
                </div>
                <label class="block text-sm font-medium">Address<textarea v-model="form.address" rows="3" class="erp-input mt-1" /></label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm font-medium">Facebook URL<input v-model="form.facebook_url" class="erp-input mt-1" /></label>
                    <label class="block text-sm font-medium">LinkedIn URL<input v-model="form.linkedin_url" class="erp-input mt-1" /></label>
                </div>
                <label class="block text-sm font-medium">About<textarea v-model="form.about" rows="5" class="erp-input mt-1" /></label>
                <label class="block text-sm font-medium">Manufacturing<textarea v-model="form.manufacturing" rows="5" class="erp-input mt-1" /></label>
                <div>
                    <p class="text-sm font-medium">Feature strip</p>
                    <input v-for="(line, index) in form.feature_strip" :key="index" v-model="form.feature_strip[index]" class="erp-input mt-2" />
                </div>
                <div class="space-y-3">
                    <p class="text-sm font-medium">Why choose us</p>
                    <div v-for="(reason, index) in form.why_choose_us" :key="index" class="grid gap-2 sm:grid-cols-2">
                        <input v-model="reason.title" class="erp-input" placeholder="Title" />
                        <input v-model="reason.body" class="erp-input" placeholder="Body" />
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.show_sample_catalog_notice" type="checkbox" :true-value="1" :false-value="0" />
                    Show the sample-catalog notice on the public site
                </label>
            </fieldset>
            <button v-if="canManage" class="erp-btn-primary" :disabled="form.processing">Save settings</button>
            <p v-else class="text-sm text-erp-muted">You can review these settings. Publishing changes needs website management permission.</p>
        </form>
    </ErpLayout>
</template>
