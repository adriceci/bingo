<script setup>
import { computed } from 'vue';
import { useNotifications } from '@/Composables/useNotifications';

const { notifications, dismiss } = useNotifications();

const styles = {
    error: 'bg-rose-600 text-white',
    info: 'bg-blue-600 text-white',
    success: 'bg-emerald-600 text-white',
};

const sorted = computed(() => notifications.value.slice().reverse());
</script>

<template>
    <div class="fixed bottom-4 right-4 z-50 space-y-2">
        <TransitionGroup name="fade">
            <div
                v-for="note in sorted"
                :key="note.id"
                :class="[
                    'flex min-w-[260px] max-w-sm items-start gap-3 rounded-lg px-4 py-3 shadow-lg ring-1 ring-black/5 dark:ring-white/10',
                    styles[note.type] ?? styles.info,
                ]"
            >
                <div class="flex-1 text-sm leading-5">{{ note.message }}</div>
                <button
                    class="text-white/80 transition hover:text-white"
                    type="button"
                    @click="dismiss(note.id)"
                >
                    ×
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(8px);
}
</style>
