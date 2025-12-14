<script setup>
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        required: true,
    },
    message: {
        type: String,
        default: '',
    },
    confirmText: {
        type: String,
        default: 'Confirmar',
    },
    cancelText: {
        type: String,
        default: 'Cancelar',
    },
    danger: {
        type: Boolean,
        default: false,
    },
    processing: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: 'md',
    },
});

const emit = defineEmits(['close', 'confirm']);

const close = () => {
    emit('close');
};

const confirm = () => {
    emit('confirm');
};
</script>

<template>
    <Modal :show="show" :max-width="maxWidth" @close="close">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ title }}
            </h2>

            <p v-if="message" class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                {{ message }}
            </p>

            <div v-if="$slots.content" class="mt-4">
                <slot name="content" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton @click="close">
                    {{ cancelText }}
                </SecondaryButton>

                <DangerButton
                    v-if="danger"
                    :class="{ 'opacity-25': processing }"
                    :disabled="processing"
                    @click="confirm"
                >
                    {{ confirmText }}
                </DangerButton>

                <button
                    v-else
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-25"
                    :disabled="processing"
                    @click="confirm"
                >
                    {{ confirmText }}
                </button>
            </div>
        </div>
    </Modal>
</template>
