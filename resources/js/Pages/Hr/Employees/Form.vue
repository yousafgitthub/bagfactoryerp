<script setup>
import ErpFormSection from '@/Components/Erp/ErpFormSection.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import InputError from '@/Components/InputError.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    employee: Object,
    lookups: Object,
});

const form = useForm({
    employee_code: props.employee?.employee_code ?? '',
    name: props.employee?.name ?? '',
    phone: props.employee?.phone ?? '',
    address: props.employee?.address ?? '',
    joining_date: props.employee?.joining_date ?? new Date().toISOString().slice(0, 10),
    department_id: props.employee?.department_id ?? '',
    designation_id: props.employee?.designation_id ?? '',
    salary_type: props.employee?.salary_type ?? 'monthly',
    basic_monthly_salary: props.employee?.basic_monthly_salary ?? '',
    daily_wage_rate: props.employee?.daily_wage_rate ?? '',
    overtime_rate: props.employee?.overtime_rate ?? '',
    overtime_rule: props.employee?.overtime_rule ?? '',
    status: props.employee?.status ?? 'active',
    notes: props.employee?.notes ?? '',
    salary_effective_from: new Date().toISOString().slice(0, 10),
    salary_rate_notes: '',
});

const submit = () => {
    if (props.employee?.id) {
        form.put(route('hr.employees.update', props.employee.id));
    } else {
        form.post(route('hr.employees.store'));
    }
};
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="pageTitle" />

        <form class="space-y-6" @submit.prevent="submit">
            <ErpFormSection title="Identity">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="erp-label">Employee code</label>
                        <input v-model="form.employee_code" class="erp-input w-full" required />
                        <InputError :message="form.errors.employee_code" />
                    </div>
                    <div>
                        <label class="erp-label">Name</label>
                        <input v-model="form.name" class="erp-input w-full" required />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div>
                        <label class="erp-label">Phone</label>
                        <input v-model="form.phone" class="erp-input w-full" />
                    </div>
                    <div>
                        <label class="erp-label">Joining date</label>
                        <input v-model="form.joining_date" type="date" class="erp-input w-full" required />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="erp-label">Address</label>
                        <textarea v-model="form.address" class="erp-input w-full" rows="2" />
                    </div>
                </div>
            </ErpFormSection>

            <ErpFormSection title="Organization">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="erp-label">Department</label>
                        <select v-model="form.department_id" class="erp-input w-full">
                            <option value="">—</option>
                            <option v-for="d in lookups.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="erp-label">Designation</label>
                        <select v-model="form.designation_id" class="erp-input w-full">
                            <option value="">—</option>
                            <option v-for="d in lookups.designations" :key="d.id" :value="d.id">
                                {{ d.name }} ({{ d.department?.name ?? '—' }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="erp-label">Status</label>
                        <select v-model="form.status" class="erp-input w-full">
                            <option v-for="(label, key) in lookups.statuses" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                </div>
            </ErpFormSection>

            <ErpFormSection title="Compensation">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="erp-label">Salary type</label>
                        <select v-model="form.salary_type" class="erp-input w-full">
                            <option v-for="(label, key) in lookups.salaryTypes" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div v-if="form.salary_type === 'monthly'">
                        <label class="erp-label">Basic monthly salary</label>
                        <input v-model="form.basic_monthly_salary" type="number" step="0.01" class="erp-input w-full" />
                    </div>
                    <div v-if="form.salary_type === 'daily'">
                        <label class="erp-label">Daily wage rate</label>
                        <input v-model="form.daily_wage_rate" type="number" step="0.01" class="erp-input w-full" />
                    </div>
                    <div>
                        <label class="erp-label">Overtime rate (per hour)</label>
                        <input v-model="form.overtime_rate" type="number" step="0.0001" class="erp-input w-full" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="erp-label">Overtime rule</label>
                        <input v-model="form.overtime_rule" class="erp-input w-full" placeholder="Optional policy note" />
                    </div>
                    <template v-if="employee?.id">
                        <div>
                            <label class="erp-label">Salary change effective from</label>
                            <input v-model="form.salary_effective_from" type="date" class="erp-input w-full" />
                            <p class="mt-1 text-xs text-erp-muted">Required when pay fields change; prior rates are closed automatically.</p>
                        </div>
                        <div>
                            <label class="erp-label">Rate history note</label>
                            <input v-model="form.salary_rate_notes" class="erp-input w-full" />
                        </div>
                    </template>
                </div>
            </ErpFormSection>

            <div class="flex gap-3">
                <button type="submit" class="erp-btn-primary" :disabled="form.processing">Save</button>
            </div>
        </form>
    </ErpLayout>
</template>