<script setup>
import { computed } from 'vue';

const props = defineProps({
    drawnNumbers: {
        type: Array,
        default: () => [],
    },
    maxNumber: {
        type: Number,
        default: 99,
    },
});

const numbers = computed(() => Array.from({ length: props.maxNumber }, (_, index) => index + 1));
const drawnSet = computed(() => new Set(props.drawnNumbers ?? []));
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-3 flex items-center justify-between">
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                Números (1-{{ maxNumber }})
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ drawnSet.size }} / {{ maxNumber }}
            </p>
        </div>
        <div class="grid grid-cols-10 gap-1 text-xs sm:text-sm">
            <div
                v-for="value in numbers"
                :key="value"
                :class="[
                    'flex h-8 items-center justify-center rounded border text-center transition',
                    drawnSet.has(value)
                        ? 'border-emerald-500 bg-emerald-50 text-emerald-800 dark:border-emerald-500/70 dark:bg-emerald-900/40 dark:text-emerald-50'
                        : 'border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200',
                ]"
            >
                {{ value.toString().padStart(2, '0') }}
            </div>
        </div>
    </div>
</template>
