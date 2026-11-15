<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    attendanceDate: String,
    employees: Array,
    existing: Object,
    statuses: Object,
    canManage: Boolean,
});

const buildRows = () =>
    props.employees.map((e) => {
        const ex = props.existing[e.id];
        return {
            employee_id: e.id,
            label: `${e.employee_code} — ${e.name}`,
            department: e.department?.name ?? '—',
            status: ex?.status ?? 'present',
            check_in_at: ex?.check_in_at?.slice?.(0, 5) ?? '',
            check_out_at: ex?.check_out_at?.slice?.(0, 5) ?? '',
            working_hours: ex?.working_hours ?? '',
            notes: ex?.notes ?? '',
        };
    });

const tableRows = ref(buildRows());

watch(
    () => [props.employees, props.existing, props.attendanceDate],
    () => {
        tableRows.value = buildRows();
    },
);

const form = useForm({
    attendance_date: props.attendanceDate,
    rows: [],
});

const changeDate = (value) => {
    router.get(route('hr.attendance.daily'), { date: value }, { preserveState: true });
};

const submit = () => {
    form.attendance_date = props.attendanceDate;
    form.rows = tableRows.value.map((r) => ({
        employee_id: r.employee_id,
        status: r.status,
        check_in_at: r.check_in_at || null,
        check_out_at: r.check_out_at || null,
        working_hours: r.working_hours || null,
        notes: r.notes || null,
    }));
    form.post(route('hr.attendance.daily.store'));
};
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Daily attendance entry"
            help-text="One row per active employee for the selected date. Saving replaces any earlier sheet for that day."
        >
            <template #actions>
                <input type="date" class="erp-input" :value="attendanceDate" @change="changeDate($event.target.value)" />
            </template>
        </ErpPageHeader>

        <form class="erp-card overflow-x-auto p-4" @submit.prevent="submit">
            <table class="erp-table w-full min-w-[720px] text-sm">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Dept</th>
                        <th>Status</th>
                        <th>In</th>
                        <th>Out</th>
                        <th>Hours</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, idx) in tableRows" :key="row.employee_id">
                        <td>{{ row.label }}</td>
                        <td>{{ row.department }}</td>
                        <td>
                            <select v-model="tableRows[idx].status" class="erp-input" :disabled="!canManage">
                                <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </td>
                        <td><input v-model="tableRows[idx].check_in_at" type="time" class="erp-input" :disabled="!canManage" /></td>
                        <td><input v-model="tableRows[idx].check_out_at" type="time" class="erp-input" :disabled="!canManage" /></td>
                        <td><input v-model="tableRows[idx].working_hours" type="number" step="0.01" class="erp-input w-20" :disabled="!canManage" /></td>
                    </tr>
                </tbody>
            </table>
            <button v-if="canManage" type="submit" class="erp-btn-primary mt-4" :disabled="form.processing">Save day</button>
        </form>
    </ErpLayout>
</template>