<script setup>
const props = defineProps({
    cardNumber: {
        type: Number,
        required: true,
    },
    numbersGrid: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-200 px-3 py-2 text-sm font-semibold text-gray-800 dark:border-gray-700 dark:text-gray-100">
            <span>Tablero #{{ cardNumber }}</span>
        </div>
        <div class="p-3">
            <div class="mt-2 grid grid-rows-3 gap-1">
                <div v-for="(row, rowIndex) in numbersGrid" :key="rowIndex" class="grid grid-cols-10 gap-1">
                    <div
                        v-for="(cell, colIndex) in row"
                        :key="colIndex"
                        :class="[
                            'h-10 rounded border text-sm font-semibold flex items-center justify-center',
                            cell
                                ? 'border-indigo-300 bg-indigo-50 text-indigo-900 dark:border-indigo-500/60 dark:bg-indigo-900/40 dark:text-indigo-50'
                                : 'border-dashed border-gray-300 bg-white text-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-600',
                        ]"
                    >
                        {{ cell ?? '—' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
