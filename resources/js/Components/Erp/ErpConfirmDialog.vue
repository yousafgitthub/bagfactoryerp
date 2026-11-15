<script setup>
import ErpDialog from '@/Components/Erp/ErpDialog.vue';

defineProps({
    show: Boolean,
    title: {
        type: String,
        default: 'Confirm action',
    },
    message: {
        type: String,
        default: 'This action cannot be undone.',
    },
    confirmLabel: {
        type: String,
        default: 'Confirm',
    },
    danger: {
        type: Boolean,
        default: true,
    },
    busy: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'confirm']);
</script>

<template>
    <ErpDialog :show="show" max-width="md" :title="title" @close="emit('close')">
        <p class="text-sm text-erp-muted">
            {{ message }}
        </p>
        <template #footer>
            <button type="button" class="erp-btn-secondary" :disabled="busy" @click="emit('close')">
                Cancel
            </button>
            <button
                type="button"
                :class="danger ? 'erp-btn-danger' : 'erp-btn-primary'"
                :disabled="busy"
                @click="emit('confirm')"
            >
                {{ confirmLabel }}
            </button>
        </template>
    </ErpDialog>
</template>