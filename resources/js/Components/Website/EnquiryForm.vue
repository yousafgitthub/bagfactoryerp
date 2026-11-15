<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    formToken: { type: String, required: true },
    mode: { type: String, required: true },
    submitHref: { type: String, required: true },
    categories: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    bagTypes: { type: Array, default: () => [] },
    printOptions: { type: Array, default: () => [] },
    prefill: { type: Object, default: () => ({}) },
});

const form = useForm({
    idempotency_token: props.formToken,
    company_website: '',
    name: '',
    email: '',
    phone: '',
    company: '',
    message: '',
    category: props.prefill.category || '',
    product_slug: props.prefill.product_slug || '',
    bag_type: '',
    size: '',
    thickness: '',
    color: '',
    print: 'none',
    quantity: '',
    artwork: null,
});

watch(
    () => props.formToken,
    (token) => {
        form.idempotency_token = token;
    },
);

const onFile = (event) => {
    form.artwork = event.target.files?.[0] ?? null;
};

const submit = () => {
    form.post(props.submitHref, {
        forceFormData: true,
        preserveScroll: true,
    });
};

const fieldClass = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-[#1d4ed8] focus:outline-none focus:ring-2 focus:ring-blue-200';
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <div class="absolute -left-[9999px] h-0 overflow-hidden" aria-hidden="true">
            <label>
                Company website
                <input v-model="form.company_website" type="text" tabindex="-1" autocomplete="off" />
            </label>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block text-sm font-semibold text-slate-700">
                Name
                <input v-model="form.name" required class="font-sans" :class="fieldClass" autocomplete="name" />
                <span v-if="form.errors.name" class="mt-1 block font-normal text-red-600">{{ form.errors.name }}</span>
            </label>
            <label class="block text-sm font-semibold text-slate-700">
                Email
                <input v-model="form.email" type="email" required class="font-sans" :class="fieldClass" autocomplete="email" />
                <span v-if="form.errors.email" class="mt-1 block font-normal text-red-600">{{ form.errors.email }}</span>
            </label>
            <label class="block text-sm font-semibold text-slate-700">
                Phone
                <input v-model="form.phone" :required="mode !== 'contact'" class="font-sans" :class="fieldClass" autocomplete="tel" />
                <span v-if="form.errors.phone" class="mt-1 block font-normal text-red-600">{{ form.errors.phone }}</span>
            </label>
            <label class="block text-sm font-semibold text-slate-700">
                Company
                <input v-model="form.company" class="font-sans" :class="fieldClass" autocomplete="organization" />
                <span v-if="form.errors.company" class="mt-1 block font-normal text-red-600">{{ form.errors.company }}</span>
            </label>
        </div>

        <div v-if="mode === 'quote'" class="grid gap-4 sm:grid-cols-2">
            <label class="block text-sm font-semibold text-slate-700">
                Category
                <select v-model="form.category" :class="fieldClass">
                    <option value="">General quote</option>
                    <option v-for="category in categories" :key="category.slug" :value="category.slug">{{ category.name }}</option>
                </select>
            </label>
            <label class="block text-sm font-semibold text-slate-700">
                Published product
                <select v-model="form.product_slug" :class="fieldClass">
                    <option value="">No specific product</option>
                    <option v-for="product in products.filter((item) => !form.category || item.category === form.category)" :key="product.slug + product.category" :value="product.slug">
                        {{ product.name }}
                    </option>
                </select>
            </label>
        </div>

        <div v-if="mode === 'custom'" class="grid gap-4 sm:grid-cols-2">
            <label class="block text-sm font-semibold text-slate-700">
                Bag type
                <select v-model="form.bag_type" required :class="fieldClass">
                    <option value="" disabled>Select a type</option>
                    <option v-for="type in bagTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                </select>
                <span v-if="form.errors.bag_type" class="mt-1 block font-normal text-red-600">{{ form.errors.bag_type }}</span>
            </label>
            <label class="block text-sm font-semibold text-slate-700">
                Size
                <input v-model="form.size" required class="font-sans" :class="fieldClass" />
                <span v-if="form.errors.size" class="mt-1 block font-normal text-red-600">{{ form.errors.size }}</span>
            </label>
            <label class="block text-sm font-semibold text-slate-700">
                Thickness
                <input v-model="form.thickness" class="font-sans" :class="fieldClass" />
            </label>
            <label class="block text-sm font-semibold text-slate-700">
                Color
                <input v-model="form.color" class="font-sans" :class="fieldClass" />
            </label>
            <label class="block text-sm font-semibold text-slate-700">
                Print
                <select v-model="form.print" :class="fieldClass">
                    <option v-for="option in printOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
            </label>
            <label class="block text-sm font-semibold text-slate-700">
                Quantity
                <input v-model="form.quantity" type="number" min="1" required class="font-sans" :class="fieldClass" />
                <span v-if="form.errors.quantity" class="mt-1 block font-normal text-red-600">{{ form.errors.quantity }}</span>
            </label>
            <label class="block text-sm font-semibold text-slate-700 sm:col-span-2">
                Artwork (JPG, PNG, WEBP, or PDF, up to 5 MB)
                <input type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" class="mt-1 block w-full text-sm" @change="onFile" />
                <span v-if="form.errors.artwork" class="mt-1 block font-normal text-red-600">{{ form.errors.artwork }}</span>
            </label>
        </div>

        <label class="block text-sm font-semibold text-slate-700">
            Message
            <textarea v-model="form.message" rows="5" :required="mode !== 'custom'" class="font-sans" :class="fieldClass" />
            <span v-if="form.errors.message" class="mt-1 block font-normal text-red-600">{{ form.errors.message }}</span>
        </label>
        <p v-if="form.errors.idempotency_token" class="text-sm text-red-600">{{ form.errors.idempotency_token }}</p>

        <button type="submit" class="rounded-full bg-[#1d4ed8] px-6 py-3 text-sm font-bold text-white hover:bg-[#1e40af] disabled:opacity-60" :disabled="form.processing">
            {{ form.processing ? 'Sending…' : 'Send enquiry' }}
        </button>
    </form>
</template>
