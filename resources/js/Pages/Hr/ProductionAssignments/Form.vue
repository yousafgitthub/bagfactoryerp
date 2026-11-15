<script setup>
import ErpFormSection from '@/Components/Erp/ErpFormSection.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import InputError from '@/Components/InputError.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    assignment: Object,
    lookups: Object,
});

const form = useForm({
    employee_id: props.assignment?.employee_id ?? '',
    assignment_type: props.assignment?.assignment_type ?? 'department',
    department_id: props.assignment?.department_id ?? '',
    machine_id: props.assignment?.machine_id ?? '',
    dana_batch_id: props.assignment?.dana_batch_id ?? '',
    bag_production_batch_id: props.assignment?.bag_production_batch_id ?? '',
    bag_production_order_id: props.assignment?.bag_production_order_id ?? '',
    assigned_from: props.assignment?.assigned_from ?? '',
    assigned_to: props.assignment?.assigned_to ?? '',
    notes: props.assignment?.notes ?? '',
});

const submit = () => {
    if (props.assignment?.id) {
        form.put(route('hr.production-assignments.update', props.assignment.id));
    } else {
        form.post(route('hr.production-assignments.store'));
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
                <select v-model="form.employee_id" class="erp-input w-full" required>
                    <option value="">Select</option>
                    <option v-for="e in lookups.employees" :key="e.id" :value="e.id">{{ e.employee_code }} — {{ e.name }}</option>
                </select>
            </div>
            <div>
                <label class="erp-label">Assignment type</label>
                <select v-model="form.assignment_type" class="erp-input w-full">
                    <option v-for="(label, key) in lookups.assignmentTypes" :key="key" :value="key">{{ label }}</option>
                </select>
                <InputError :message="form.errors.assignment_type" />
            </div>
            <div v-if="form.assignment_type === 'department'">
                <label class="erp-label">Department</label>
                <select v-model="form.department_id" class="erp-input w-full">
                    <option value="">Select</option>
                    <option v-for="d in lookups.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
            </div>
            <div v-if="form.assignment_type === 'dana_machine' || form.assignment_type === 'bag_machine'">
                <label class="erp-label">Machine</label>
                <select v-model="form.machine_id" class="erp-input w-full">
                    <option value="">Select</option>
                    <option v-for="m in lookups.machines" :key="m.id" :value="m.id">{{ m.code }} — {{ m.name }}</option>
                </select>
            </div>
            <div v-if="form.assignment_type === 'dana_batch'">
                <label class="erp-label">Dana batch</label>
                <select v-model="form.dana_batch_id" class="erp-input w-full">
                    <option value="">Select</option>
                    <option v-for="b in lookups.danaBatches" :key="b.id" :value="b.id">{{ b.batch_number }}</option>
                </select>
            </div>
            <div v-if="form.assignment_type === 'bag_batch'">
                <label class="erp-label">Bag batch</label>
                <select v-model="form.bag_production_batch_id" class="erp-input w-full">
                    <option value="">Select</option>
                    <option v-for="b in lookups.bagBatches" :key="b.id" :value="b.id">{{ b.batch_number }}</option>
                </select>
            </div>
            <div v-if="form.assignment_type === 'bag_order'">
                <label class="erp-label">Bag order</label>
                <select v-model="form.bag_production_order_id" class="erp-input w-full">
                    <option value="">Select</option>
                    <option v-for="o in lookups.bagOrders" :key="o.id" :value="o.id">{{ o.order_number }}</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="erp-label">From</label>
                    <input v-model="form.assigned_from" type="date" class="erp-input w-full" />
                </div>
                <div>
                    <label class="erp-label">To</label>
                    <input v-model="form.assigned_to" type="date" class="erp-input w-full" />
                </div>
            </div>
            <div>
                <label class="erp-label">Notes</label>
                <textarea v-model="form.notes" class="erp-input w-full" rows="2" />
            </div>
            <button type="submit" class="erp-btn-primary" :disabled="form.processing">Save</button>
        </form>
    </ErpLayout>
</template>