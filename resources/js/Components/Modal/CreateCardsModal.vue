<script setup>
import { ref } from 'vue';
import FormModal from '@/Components/FormModal.vue';

const props = defineProps({
	show: {
		type: Boolean,
		required: true,
	},
	loading: {
		type: Boolean,
		default: false,
	},
	canGenerate: {
		type: Boolean,
		default: true,
	},
	maxCardsPerUser: {
		type: Number,
		default: 5,
	},
	currentCardsCount: {
		type: Number,
		default: 0,
	},
});

const emit = defineEmits(['close', 'generate']);

const countToGenerate = ref(1);

const handleGenerate = () => {
	emit('generate', countToGenerate.value);
	countToGenerate.value = 1;
};

const handleClose = () => {
	countToGenerate.value = 1;
	emit('close');
};
</script>

<template>
	<FormModal 
		:show="show" 
		title="Generar tableros"
		@close="handleClose"
	>
		<template #content>
			<div>
				<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
					Cantidad de tableros
				</label>
				<input
					v-model.number="countToGenerate"
					class="w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
					min="1"
					:max="maxCardsPerUser"
					type="number"
				/>
				<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
					Máximo de {{ maxCardsPerUser }} tableros por persona
				</p>
				<p 
					v-if="currentCardsCount > 0"
					class="mt-1 text-sm text-gray-600 dark:text-gray-400"
				>
					Tienes {{ currentCardsCount }} tablero{{ currentCardsCount !== 1 ? 's' : '' }} activo{{ currentCardsCount !== 1 ? 's' : '' }}
				</p>
			</div>
		</template>

		<template #actions>
			<button
				class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
				type="button"
				@click="handleClose"
			>
				Cancelar
			</button>
			<button
				class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
				:disabled="loading"
				type="button"
				@click="handleGenerate"
			>
				{{ loading ? 'Generando...' : 'Generar' }}
			</button>
		</template>
	</FormModal>
</template>
