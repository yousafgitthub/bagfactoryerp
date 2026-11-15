<script setup>
import ErpFormField from '@/Components/Erp/ErpFormField.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    pageTitle: { type: String, required: true },
    breadcrumbs: { type: Array, default: () => [] },
    resource: { type: String, required: true },
    resourceLabel: { type: String, required: true },
    fields: { type: Array, required: true },
    record: { type: Object, default: null },
    relationOptions: { type: Object, default: () => ({}) },
});

const initial = () => {
    const data = {};

    props.fields.forEach((field) => {
        data[field.name] = props.record?.[field.name] ?? (field.type === 'status' ? 'active' : '');
    });

    return data;
};

const form = useForm(initial());

const submit = () => {
    if (props.record?.id) {
        form.put(route('master-data.update', { resource: props.resource, record: props.record.id }));

        return;
    }

    form.post(route('master-data.store', props.resource));
};

const cancelHref = computed(() => route('master-data.index', props.resource));
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="pageTitle" :description="`Maintain ${resourceLabel.toLowerCase()} master records.`" />

        <form class="erp-card max-w-3xl space-y-4 p-6" @submit.prevent="submit">
            <ErpFormField
                v-for="field in fields"
                :key="field.name"
                :label="field.label"
                :error="form.errors[field.name]"
            >
                <textarea
                    v-if="field.type === 'textarea'"
                    v-model="form[field.name]"
                    class="erp-input w-full"
                    rows="3"
                />
                <select
                    v-else-if="field.type === 'relation'"
                    v-model="form[field.name]"
                    class="erp-input w-full"
                >
                    <option value="">— Select —</option>
                    <option
                        v-for="option in relationOptions[field.name] ?? []"
                        :key="option.id"
                        :value="option.id"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <select
                    v-else-if="field.type === 'status'"
                    v-model="form[field.name]"
                    class="erp-input w-full"
                >
                    <option v-for="status in field.options" :key="status" :value="status">
                        {{ status.replaceAll('_', ' ') }}
                    </option>
                </select>
                <input
                    v-else
                    v-model="form[field.name]"
                    class="erp-input w-full"
                    :type="field.type === 'number' ? 'number' : field.type === 'date' ? 'date' : field.type === 'color' ? 'color' : 'text'"
                    :required="field.required"
                    step="any"
                />
            </ErpFormField>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-erp-border pt-4">
                <Link :href="cancelHref" class="erp-btn-secondary">Cancel</Link>
                <button type="submit" class="erp-btn-primary" :disabled="form.processing">Save</button>
            </div>
        </form>
    </ErpLayout>
</template>