<script setup>
import ErpEmptyState from '@/Components/Erp/ErpEmptyState.vue';
import ErpFormSection from '@/Components/Erp/ErpFormSection.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    employee: Object,
    canManage: Boolean,
    salaryTypes: Object,
});
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader
            :title="employee.name"
            :description="employee.employee_code"
            help-text="Salary rate history drives payroll calculations. Keep assignments updated for production reporting."
        >
            <template #actions>
                <Link v-if="canManage" :href="route('hr.employees.edit', employee.id)" class="erp-btn-secondary">Edit</Link>
            </template>
        </ErpPageHeader>

        <div class="grid gap-6 lg:grid-cols-2">
            <ErpFormSection title="Profile">
                <dl class="grid gap-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-erp-muted">Status</dt>
                        <dd><ErpStatusBadge :status="employee.status" /></dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-erp-muted">Department</dt>
                        <dd>{{ employee.department?.name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-erp-muted">Designation</dt>
                        <dd>{{ employee.designation?.name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-erp-muted">Phone</dt>
                        <dd>{{ employee.phone ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-erp-muted">Joined</dt>
                        <dd>{{ employee.joining_date }}</dd>
                    </div>
                </dl>
            </ErpFormSection>

            <ErpFormSection title="Current pay">
                <dl class="grid gap-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-erp-muted">Salary type</dt>
                        <dd>{{ salaryTypes[employee.salary_type] ?? employee.salary_type }}</dd>
                    </div>
                    <div v-if="employee.salary_type === 'monthly'" class="flex justify-between gap-4">
                        <dt class="text-erp-muted">Monthly basic</dt>
                        <dd>{{ employee.basic_monthly_salary }}</dd>
                    </div>
                    <div v-if="employee.salary_type === 'daily'" class="flex justify-between gap-4">
                        <dt class="text-erp-muted">Daily wage</dt>
                        <dd>{{ employee.daily_wage_rate }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-erp-muted">Overtime rate</dt>
                        <dd>{{ employee.overtime_rate ?? '—' }}</dd>
                    </div>
                    <div v-if="employee.overtime_rule" class="flex justify-between gap-4">
                        <dt class="text-erp-muted">Overtime rule</dt>
                        <dd>{{ employee.overtime_rule }}</dd>
                    </div>
                </dl>
            </ErpFormSection>
        </div>

        <ErpFormSection title="Salary rate history" class="mt-6">
            <table class="erp-table w-full text-sm">
                <thead>
                    <tr>
                        <th>Effective from</th>
                        <th>Effective to</th>
                        <th>Type</th>
                        <th>Monthly</th>
                        <th>Daily</th>
                        <th>OT rate</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="rate in employee.salary_rates" :key="rate.id">
                        <td>{{ rate.effective_from }}</td>
                        <td>{{ rate.effective_to ?? 'Open' }}</td>
                        <td>{{ salaryTypes[rate.salary_type] ?? rate.salary_type }}</td>
                        <td>{{ rate.basic_monthly_salary ?? '—' }}</td>
                        <td>{{ rate.daily_wage_rate ?? '—' }}</td>
                        <td>{{ rate.overtime_rate ?? '—' }}</td>
                    </tr>
                    <tr v-if="!employee.salary_rates?.length">
                        <td colspan="6" class="p-0">
                            <ErpEmptyState title="No salary history yet." description="Edit the employee to add effective pay rates." class="border-0 shadow-none" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </ErpFormSection>

        <ErpFormSection v-if="employee.production_assignments?.length" title="Recent production assignments" class="mt-6">
            <ul class="divide-y text-sm">
                <li v-for="a in employee.production_assignments" :key="a.id" class="py-2">
                    {{ a.assignment_type }} — {{ a.machine?.name ?? a.department?.name ?? a.dana_batch?.batch_number ?? a.bag_production_batch?.batch_number ?? '—' }}
                </li>
            </ul>
        </ErpFormSection>
    </ErpLayout>
</template>