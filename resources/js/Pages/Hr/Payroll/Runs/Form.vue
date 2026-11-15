<script setup>
import ErpFormSection from '@/Components/Erp/ErpFormSection.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    defaults: Object,
});

const form = useForm({
    period_start: props.defaults.period_start,
    period_end: props.defaults.period_end,
    notes: '',
});

const submit = () => form.post(route('hr.payroll.runs.store'));
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="New payroll run" description="Define the payroll period before generating employee lines." />

        <form class="erp-card max-w-xl space-y-4 p-6" @submit.prevent="submit">
            <ErpFormSection title="Period">
                <label class="block text-sm">
                    <span class="text-erp-muted">Period start</span>
                    <input v-model="form.period_start" type="date" class="erp-input mt-1 w-full" required />
                </label>
                <label class="block text-sm">
                    <span class="text-erp-muted">Period end</span>
                    <input v-model="form.period_end" type="date" class="erp-input mt-1 w-full" required />
                </label>
                <label class="block text-sm">
                    <span class="text-erp-muted">Notes</span>
                    <textarea v-model="form.notes" class="erp-input mt-1 w-full" rows="3" />
                </label>
            </ErpFormSection>
            <button type="submit" class="erp-btn-primary" :disabled="form.processing">Create run</button>
        </form>
    </ErpLayout>
</template>