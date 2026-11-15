<script setup>
import { onMounted } from 'vue';

defineProps({
    pageTitle: String,
    title: String,
    description: String,
    columns: Array,
    rows: Array,
    summary: Object,
    filters: Object,
});

onMounted(() => {
    setTimeout(() => window.print(), 300);
});
</script>

<template>
    <div class="p-6 text-sm text-black">
        <h1 class="text-xl font-bold">{{ title }}</h1>
        <p v-if="description" class="text-gray-600">{{ description }}</p>
        <p v-if="filters?.from" class="mt-1 text-gray-500">Period: {{ filters.from }} – {{ filters.to }}</p>
        <table class="mt-4 w-full border-collapse text-left">
            <thead>
                <tr>
                    <th v-for="col in columns" :key="col.key" class="border border-gray-300 px-2 py-1">{{ col.label }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, idx) in rows" :key="idx">
                    <td v-for="col in columns" :key="col.key" class="border border-gray-300 px-2 py-1">{{ row[col.key] }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<style scoped>
@media print {
    @page {
        margin: 12mm;
    }
}
</style>