<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    pageTitle: String,
    batch: Object,
});
</script>

<template>
    <Head :title="pageTitle" />

    <style scoped>
        .print-root {
            font-family: system-ui, sans-serif;
            padding: 24px;
            color: #111;
        }
    </style>

    <h1 class="text-2xl font-bold print-root">Dana batch {{ batch.batch_number }}</h1>
    <p class="print-root text-sm">Production date: {{ batch.production_date }} · Machine: {{ batch.machine?.name }}</p>

    <table class="print-root mt-6 w-full border-collapse text-sm">
        <thead>
            <tr>
                <th class="border p-2 text-left">Material</th>
                <th class="border p-2 text-right">KG</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="line in batch.material_inputs ?? []" :key="line.id">
                <td class="border p-2">{{ line.material?.name }}</td>
                <td class="border p-2 text-right">{{ line.quantity_kg }}</td>
            </tr>
        </tbody>
    </table>

    <p class="print-root mt-4">Output Dana: {{ batch.output_kg }} KG · Status: {{ batch.status }}</p>
</template>