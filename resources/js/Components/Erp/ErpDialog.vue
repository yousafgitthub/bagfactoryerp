<script setup>
import Modal from '@/Components/Modal.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: 'lg',
    },
    title: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close']);
</script>

<template>
    <Modal :show="show" :max-width="maxWidth" @close="emit('close')">
        <div class="p-6">
            <h3 v-if="title" class="text-lg font-semibold text-slate-900">{{ title }}</h3>
            <div :class="title ? 'mt-4' : ''">
                <slot />
            </div>
            <div v-if="$slots.footer" class="mt-6 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
                <slot name="footer" />
            </div>
        </div>
    </Modal>
</template>