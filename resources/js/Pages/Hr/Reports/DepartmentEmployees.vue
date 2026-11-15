<script setup>
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, router } from '@inertiajs/vue3';

defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    departments: Array,
    grouped: Object,
    departmentLabels: Object,
    selectedDepartmentId: Number,
});
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Department roster">
            <template #actions>
                <select
                    class="erp-input"
                    :value="selectedDepartmentId || ''"
                    @change="router.get(route('hr.reports.department-employees'), { department_id: $event.target.value || null })"
                >
                    <option value="">All departments</option>
                    <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
            </template>
        </ErpPageHeader>

        <div v-for="(list, deptKey) in grouped" :key="deptKey" class="erp-card mb-4 p-4">
            <h3 class="mb-3 text-base font-semibold">
                {{ departmentLabels[deptKey] ?? 'Unassigned' }}
            </h3>
            <ul class="divide-y text-sm">
                <li v-for="emp in list" :key="emp.id" class="flex justify-between py-2">
                    <span>{{ emp.employee_code }} — {{ emp.name }}</span>
                    <span class="text-erp-muted">{{ emp.designation?.name ?? '—' }}</span>
                </li>
            </ul>
        </div>
    </ErpLayout>
</template>