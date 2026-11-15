<script setup>
import InputError from '@/Components/InputError.vue';

defineProps({
    label: {
        type: String,
        required: true,
    },
    name: {
        type: String,
        required: true,
    },
    help: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="space-y-1">
        <label :for="name" class="block text-sm font-medium text-slate-900 dark:text-slate-100">
            {{ label }}
            <span v-if="required" class="text-red-600" aria-hidden="true">*</span>
        </label>
        <p v-if="help" :id="`${name}-help`" class="text-xs text-erp-muted">
            {{ help }}
        </p>
        <slot :described-by="help ? `${name}-help` : undefined" />
        <InputError :message="error" />
    </div>
</template>