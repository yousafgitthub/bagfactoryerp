<script setup>
import ErpFormSection from '@/Components/Erp/ErpFormSection.vue';
import ErpPageHeader from '@/Components/Erp/ErpPageHeader.vue';
import ErpLayout from '@/Layouts/ErpLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ pageTitle: String, breadcrumbs: Array, bom: Object, lookups: Object });

const variants = computed(() => {
    const product = props.lookups.products.find((p) => p.id === Number(form.bag_product_id));
    return product?.variants ?? [];
});

const form = useForm({
    code: props.bom?.code ?? '',
    name: props.bom?.name ?? '',
    bag_product_id: props.bom?.bag_product_id ?? '',
    bag_product_variant_id: props.bom?.bag_product_variant_id ?? '',
    effective_from: props.bom?.effective_from ?? new Date().toISOString().slice(0, 10),
    effective_to: props.bom?.effective_to ?? '',
    status: props.bom?.status ?? 'active',
    notes: props.bom?.notes ?? '',
    lines:
        props.bom?.lines?.map((l) => ({
            material_id: l.material_id,
            quantity_per_bag: l.quantity_per_bag,
            wastage_percent: l.wastage_percent ?? 0,
        })) ?? [{ material_id: '', quantity_per_bag: '', wastage_percent: 0 }],
});

const addLine = () => form.lines.push({ material_id: '', quantity_per_bag: '', wastage_percent: 0 });

const submit = () => {
    if (props.bom?.id) form.put(route('bag.bom.update', props.bom.id));
    else form.post(route('bag.bom.store'));
};
</script>

<template>
    <Head :title="pageTitle" />
    <ErpLayout :page-title="pageTitle" :breadcrumbs="breadcrumbs">
        <ErpPageHeader :title="pageTitle">
            <template #actions>
                <Link v-if="bom?.id" :href="route('bag.bom.new-version', bom.id)" method="post" as="button" class="erp-btn-secondary">New version</Link>
            </template>
        </ErpPageHeader>
        <form class="space-y-4" @submit.prevent="submit">
            <ErpFormSection title="Header">
                <input v-model="form.code" class="erp-input" required />
                <input v-model="form.name" class="erp-input" required />
                <select v-model="form.bag_product_id" class="erp-input" required>
                    <option v-for="p in lookups.products" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
                <select v-model="form.bag_product_variant_id" class="erp-input" required>
                    <option v-for="v in variants" :key="v.id" :value="v.id">{{ v.variant_code }}</option>
                </select>
                <input v-model="form.effective_from" type="date" class="erp-input" required />
            </ErpFormSection>
            <ErpFormSection title="Lines (per bag)">
                <div v-for="(line, i) in form.lines" :key="i" class="grid gap-2 sm:grid-cols-3">
                    <select v-model="line.material_id" class="erp-input">
                        <option v-for="m in lookups.materials" :key="m.id" :value="m.id">{{ m.name }} ({{ m.inventory_bucket }})</option>
                    </select>
                    <input v-model="line.quantity_per_bag" type="number" step="0.000001" class="erp-input" placeholder="Qty/bag (g or kg)" />
                    <input v-model="line.wastage_percent" type="number" step="0.01" class="erp-input" placeholder="Wastage %" />
                </div>
                <button type="button" class="erp-btn-secondary" @click="addLine">+ Line</button>
            </ErpFormSection>
            <button type="submit" class="erp-btn-primary">Save BOM</button>
        </form>
    </ErpLayout>
</template>