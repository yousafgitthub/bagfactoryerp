<script setup>
import ErpLayout from '@/Layouts/ErpLayout.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <ErpLayout
        page-title="Profile"
        :breadcrumbs="[
            { label: 'Dashboard', href: route('dashboard') },
            { label: 'Profile' },
        ]"
    >
        <ErpPageHeader
            title="Your profile"
            description="Update your account details and password."
        />

        <div class="mx-auto max-w-3xl space-y-6">
            <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <UpdatePasswordForm class="max-w-xl" />
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <DeleteUserForm class="max-w-xl" />
            </section>
        </div>
    </ErpLayout>
</template>