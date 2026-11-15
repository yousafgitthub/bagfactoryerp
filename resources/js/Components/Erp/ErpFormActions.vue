<script setup>
defineProps({
    submitting: {
        type: Boolean,
        default: false,
    },
    showDraft: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['cancel', 'draft', 'submit']);
</script>

<template>
    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-erp-border pt-4">
        <button type="button" class="erp-btn-secondary" :disabled="submitting" @click="$emit('cancel')">
            Cancel
        </button>
        <button
            v-if="showDraft"
            type="button"
            class="erp-btn-secondary"
            :disabled="submitting"
            @click="$emit('draft')"
        >
            Save draft
        </button>
        <button type="submit" class="erp-btn-primary" :disabled="submitting" @click="$emit('submit')">
            <slot name="submit-label">Save & post</slot>
        </button>
    </div>
</template>