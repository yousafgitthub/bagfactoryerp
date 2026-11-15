<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpStatusBadge from '@/Components/Erp/ErpStatusBadge.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    run: Object,
    canManage: Boolean,
    canApprove: Boolean,
});

const cancelForm = useForm({ reason: '' });

const postAction = (name) => router.post(route(name, props.run.id));

const submitCancel = () => cancelForm.post(route('hr.payroll.runs.cancel', props.run.id));
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="'Payroll ' + run.period_label">
            <template #actions>
                <span class="mr-2"><ErpStatusBadge :status="run.status" /></span>
                <button
                    v-if="canManage && ['draft', 'reviewed'].includes(run.status)"
                    type="button"
                    class="erp-btn-secondary"
                    @click="postAction('hr.payroll.runs.generate')"
                >
                    Generate / refresh
                </button>
                <button v-if="canManage && run.status === 'draft'" type="button" class="erp-btn-secondary" @click="postAction('hr.payroll.runs.review')">
                    Mark reviewed
                </button>
                <button
                    v-if="canApprove && ['draft', 'reviewed'].includes(run.status)"
                    type="button"
                    class="erp-btn-primary"
                    @click="postAction('hr.payroll.runs.approve')"
                >
                    Approve
                </button>
            </template>
        </ErpPageHeader>

        <div class="mb-4 grid gap-4 sm:grid-cols-3">
            <div class="erp-card p-4 text-sm">
                <div class="text-erp-muted">Gross</div>
                <div class="text-lg font-semibold tabular-nums">{{ run.total_gross }}</div>
            </div>
            <div class="erp-card p-4 text-sm">
                <div class="text-erp-muted">Net payable</div>
                <div class="text-lg font-semibold tabular-nums">{{ run.total_net }}</div>
            </div>
            <div class="erp-card p-4 text-sm">
                <div class="text-erp-muted">Paid</div>
                <div class="text-lg font-semibold tabular-nums">{{ run.total_paid }}</div>
            </div>
        </div>

        <div class="erp-card p-4">
            <ErpDataTable
                :columns="[
                    { key: 'employee', label: 'Employee' },
                    { key: 'type', label: 'Pay type' },
                    { key: 'days', label: 'Payable days' },
                    { key: 'net', label: 'Net' },
                    { key: 'outstanding', label: 'Outstanding' },
                    { key: 'status', label: 'Status' },
                    { key: 'actions', label: '' },
                ]"
                :rows="
                    (run.records || []).map((rec) => ({
                        id: rec.id,
                        employee: rec.employee?.name,
                        type: rec.salary_type,
                        days: rec.payable_days,
                        net: rec.net_payable,
                        outstanding: rec.outstanding_amount,
                        status: rec.status,
                    }))
                "
            >
                <template #cell-status="{ row }">
                    <ErpStatusBadge :status="row.status" />
                </template>
                <template #cell-actions="{ row }">
                    <Link :href="route('hr.payroll.records.show', row.id)" class="erp-link text-sm">Details</Link>
                </template>
            </ErpDataTable>
        </div>

        <form v-if="canApprove && !['paid', 'cancelled'].includes(run.status)" class="erp-card mt-6 max-w-lg space-y-2 p-4" @submit.prevent="submitCancel">
            <h3 class="font-medium">Cancel run</h3>
            <input v-model="cancelForm.reason" type="text" class="erp-input w-full" placeholder="Reason" required />
            <button type="submit" class="erp-btn-secondary text-red-700" :disabled="cancelForm.processing">Cancel payroll run</button>
        </form>
    </ErpLayout>
</template>