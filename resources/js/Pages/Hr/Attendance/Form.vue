<script setup>
import ErpFormSection from '@/Components/Erp/ErpFormSection.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import InputError from '@/Components/InputError.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    attendance: Object,
    lookups: Object,
});

const form = useForm({
    employee_id: props.attendance?.employee_id ?? '',
    attendance_date: props.attendance?.attendance_date ?? new Date().toISOString().slice(0, 10),
    status: props.attendance?.status ?? 'present',
    check_in_at: props.attendance?.check_in_at?.slice?.(0, 5) ?? '',
    check_out_at: props.attendance?.check_out_at?.slice?.(0, 5) ?? '',
    working_hours: props.attendance?.working_hours ?? '',
    notes: props.attendance?.notes ?? '',
});

const submit = () => {
    if (props.attendance?.id) {
        form.put(route('hr.attendance.update', props.attendance.id));
    } else {
        form.post(route('hr.attendance.store'));
    }
};
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="pageTitle" />

        <form class="erp-card max-w-xl space-y-4 p-6" @submit.prevent="submit">
            <div>
                <label class="erp-label">Employee</label>
                <select v-model="form.employee_id" class="erp-input w-full" required :disabled="!!attendance?.id">
                    <option value="">Select</option>
                    <option v-for="e in lookups.employees" :key="e.id" :value="e.id">{{ e.employee_code }} — {{ e.name }}</option>
                </select>
                <InputError :message="form.errors.employee_id" />
            </div>
            <div>
                <label class="erp-label">Date</label>
                <input v-model="form.attendance_date" type="date" class="erp-input w-full" required />
                <InputError :message="form.errors.attendance_date" />
            </div>
            <div>
                <label class="erp-label">Status</label>
                <select v-model="form.status" class="erp-input w-full">
                    <option v-for="(label, key) in lookups.statuses" :key="key" :value="key">{{ label }}</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="erp-label">Check in</label>
                    <input v-model="form.check_in_at" type="time" class="erp-input w-full" />
                </div>
                <div>
                    <label class="erp-label">Check out</label>
                    <input v-model="form.check_out_at" type="time" class="erp-input w-full" />
                </div>
            </div>
            <div>
                <label class="erp-label">Working hours (optional override)</label>
                <input v-model="form.working_hours" type="number" step="0.01" class="erp-input w-full" />
            </div>
            <div>
                <label class="erp-label">Notes</label>
                <textarea v-model="form.notes" class="erp-input w-full" rows="2" />
            </div>
            <button type="submit" class="erp-btn-primary" :disabled="form.processing">Save</button>
        </form>
    </ErpLayout>
</template>