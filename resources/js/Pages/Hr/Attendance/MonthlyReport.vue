<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    month: String,
    rows: Array,
    statuses: Object,
    lookups: Object,
    departmentId: Number,
});

const apply = (patch) => {
    router.get(route('hr.attendance.monthly'), { month: props.month, department_id: props.departmentId, ...patch }, { preserveState: true });
};
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            title="Monthly attendance report"
            help-text="Pick a calendar month and optional department to see present, absent, and late counts per employee."
        >
            <template #actions>
                <input type="month" class="erp-input" :value="month" @change="apply({ month: $event.target.value })" />
                <select class="erp-input" :value="departmentId || ''" @change="apply({ department_id: $event.target.value || null })">
                    <option value="">All departments</option>
                    <option v-for="d in lookups.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
            </template>
        </ErpPageHeader>

        <div class="erp-card overflow-x-auto p-4">
            <table class="erp-table w-full min-w-[640px] text-sm">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th v-for="(label, key) in statuses" :key="key">{{ label }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.employee.id">
                        <td>{{ row.employee.employee_code }} — {{ row.employee.name }}</td>
                        <td v-for="(label, key) in statuses" :key="key">{{ row.counts[key] ?? 0 }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </ErpLayout>
</template>