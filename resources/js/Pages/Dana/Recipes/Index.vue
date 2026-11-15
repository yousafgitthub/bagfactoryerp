<script setup>
import ErpDataTable from '@/Components/Erp/ErpDataTable.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    pageTitle: String,
    breadcrumbs: Array,
    recipes: Object,
    canManage: Boolean,
});
</script>

<template>
    <Head :title="pageTitle" />

    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader title="Dana recipes">
            <template #actions>
                <Link v-if="canManage" :href="route('dana.recipes.create')" class="erp-btn-primary">+ New recipe</Link>
            </template>
        </ErpPageHeader>

        <ErpDataTable
            title="Recipes"
            :columns="[
                { key: 'code', label: 'Code' },
                { key: 'name', label: 'Name' },
                { key: 'output', label: 'Output material' },
                { key: 'lines', label: 'Lines' },
                { key: 'loss', label: 'Max loss %' },
            ]"
            :rows="
                (recipes.data ?? []).map((r) => ({
                    id: r.id,
                    code: r.code,
                    name: r.name,
                    output: r.output_material?.name,
                    lines: r.lines_count,
                    loss: r.max_process_loss_percent,
                }))
            "
        />
        <p v-if="canManage" class="mt-2 text-sm">
            <Link
                v-for="r in recipes.data ?? []"
                :key="r.id"
                :href="route('dana.recipes.edit', r.id)"
                class="mr-3 text-erp-primary hover:underline"
            >
                Edit {{ r.code }}
            </Link>
        </p>
    </ErpLayout>
</template>