<script setup>
const props = defineProps({
    players: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-2 flex items-center justify-between text-sm font-semibold text-gray-800 dark:text-gray-100">
            <span>Jugadores conectados</span>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ players.length }}</span>
        </div>
        <ul class="space-y-1 text-sm text-gray-700 dark:text-gray-200">
            <li v-if="players.length === 0" class="text-gray-500 dark:text-gray-400">
                Sin jugadores conectados
            </li>
            <li v-for="player in players" :key="player.id" class="flex items-center gap-2">
                <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                <span>{{ player.name ?? 'Jugador' }}</span>
            </li>
        </ul>
    </div>
</template>
