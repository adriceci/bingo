<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import NumberGrid from '@/Components/Bingo/NumberGrid.vue';
import BingoCardDisplay from '@/Components/Bingo/BingoCardDisplay.vue';
import PlayersList from '@/Components/Bingo/PlayersList.vue';
import NotificationCenter from '@/Components/NotificationCenter.vue';
import CreateCardsModal from '@/Components/Modal/CreateCardsModal.vue';
import { notify, notifyError } from '@/Composables/useNotifications';

const page = usePage();
const currentUserId = computed(() => page.props.auth.user.id);

const props = defineProps({
    game: {
        type: Object,
        required: true,
    },
    cards: {
        type: Array,
        default: () => [],
    },
    archivedCount: {
        type: Number,
        default: 0,
    },
    maxActiveCards: {
        type: Number,
        default: 100,
    },
});

const state = reactive({
    status: props.game.status,
    drawnNumbers: [...props.game.drawnNumbers],
    cards: [...props.cards],
    archivedCount: props.archivedCount,
    players: [],
    loading: {
        draw: false,
        generate: false,
        reset: false,
        close: false,
    },
});

const showGenerateModal = ref(false);
const maxCardsPerUser = 5;
const channel = ref(null);
const channelName = `bingo-game.${props.game.id}`;

const remainingNumbers = computed(
    () => props.game.maxNumber - state.drawnNumbers.length,
);

const isGameCreator = computed(() => props.game.user_id === currentUserId.value);

const canDraw = computed(
    () => state.status === 'active' && remainingNumbers.value > 0 && state.cards.length > 0 && isGameCreator.value,
);

const canGenerateCards = computed(
    () => state.status === 'active' && state.cards.length < maxCardsPerUser,
);

const shareUrl = computed(() => `${window.location.origin}/bingo/${props.game.id}`);

const totalCards = computed(() => state.cards.length);

const listenEvents = () => {
    if (!window.Echo) {
        notify('Echo no está configurado. Verifica las variables Reverb.', 'error');
        return;
    }

    channel.value = window.Echo.join(channelName)
        .here((users) => {
            state.players = users;
        })
        .joining((user) => {
            state.players = [...state.players, user];
        })
        .leaving((user) => {
            state.players = state.players.filter((item) => item.id !== user.id);
        })
        .listen('GameStarted', (payload) => {
            state.status = payload.status ?? 'active';
            state.drawnNumbers = payload.drawnNumbers ?? [];
        })
        .listen('NumberDrawn', (payload) => {
            state.status = 'active';
            state.drawnNumbers = payload.drawnNumbers ?? state.drawnNumbers;
        })
        .listen('CardsGenerated', (payload) => {
            const myCards = (payload.cards ?? []).filter(
                card => card.userId === currentUserId.value
            );
            if (myCards.length > 0) {
                state.cards = [...state.cards, ...myCards];
            }
        })
        .listen('GameReset', (payload) => {
            state.status = payload.status ?? 'active';
            state.drawnNumbers = payload.drawnNumbers ?? [];
            state.cards = [];
            state.archivedCount = payload.archivedCount ?? state.archivedCount;
        })
        .listen('GameClosed', (payload) => {
            state.status = payload.status ?? 'closed';
            notify('La partida fue cerrada', 'info');
        })
        .listen('LineCompleted', (payload) => {
            state.status = 'active';
            if (payload.userId === currentUserId.value) {
                notify(`¡LÍNEA! Tablero ${payload.cardNumber}`, 'success');
            } else {
                notify(`¡LÍNEA! Un jugador completó una línea`, 'info');
            }
        })
        .listen('BingoWon', (payload) => {
            state.status = 'closed';
            if (payload.userId === currentUserId.value) {
                notify(`¡¡BINGO!! ¡¡Ganaste con el tablero ${payload.cardNumber}!!`, 'success');
            } else {
                notify(`¡¡BINGO!! Un jugador ganó la partida`, 'info');
            }
        });
};

onMounted(listenEvents);

onBeforeUnmount(() => {
    if (channel.value) {
        channel.value.stopListening('GameStarted');
        channel.value.stopListening('NumberDrawn');
        channel.value.stopListening('CardsGenerated');
        channel.value.stopListening('GameReset');
        channel.value.stopListening('GameClosed');
        channel.value.stopListening('LineCompleted');
        channel.value.stopListening('BingoWon');
        window.Echo.leave(channelName);
    }
});

const handleError = (error) => {
    notifyError(error);
};

const postAction = async (routeName, payload = {}, busyKey = null) => {
    if (busyKey) state.loading[busyKey] = true;
    try {
        const { data } = await window.axios.post(route(routeName, props.game.id), payload);
        return data;
    } catch (error) {
        handleError(error);
        return null;
    } finally {
        if (busyKey) state.loading[busyKey] = false;
    }
};

const drawNumber = async () => {
    if (!canDraw.value) {
        notify('No se puede sacar otro número ahora.', 'error');
        return;
    }

    const data = await postAction('bingo.draw', {}, 'draw');
    if (data?.game?.drawnNumbers) {
        state.drawnNumbers = data.game.drawnNumbers;
    }
    if (data?.number) {
        notify(`Número sacado: ${data.number}`, 'success');
    }
};

const generateCards = async (count) => {
    if (!canGenerateCards.value) {
        notify('No puedes generar más tableros en esta partida.', 'error');
        return;
    }

    const amount = Math.min(count || 1, maxCardsPerUser);

    const data = await postAction(
        'bingo.cards',
        { count: amount },
        'generate',
    );

    if (data?.cards) {
        state.cards = [...state.cards, ...data.cards];
        showGenerateModal.value = false;
    }
};

const openGenerateModal = () => {
    if (!canGenerateCards.value) {
        notify('No puedes generar más tableros en esta partida.', 'error');
        return;
    }
    showGenerateModal.value = true;
};

const resetGame = async () => {
    if (state.status === 'closed') {
        notify('La partida está cerrada.', 'error');
        return;
    }

    const data = await postAction('bingo.reset', {}, 'reset');
    if (data) {
        state.drawnNumbers = data.game?.drawnNumbers ?? [];
        state.cards = [];
        state.archivedCount = data.archivedCount ?? state.archivedCount;
        state.status = data.game?.status ?? 'active';
    }
};

const shareGame = async () => {
    try {
        await navigator.clipboard.writeText(shareUrl.value);
        notify('URL de la partida copiada al portapapeles.', 'success');
    } catch (error) {
        notifyError(error);
    }
};

const closeGame = async () => {
    if (state.status === 'closed') {
        notify('La partida ya está cerrada.', 'info');
        return;
    }

    const data = await postAction('bingo.close', {}, 'close');
    if (data) {
        state.status = data.status ?? 'closed';
    }
};

</script>

<template>
    <Head title="Bingo" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Bingo
                    </h2>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        :href="route('bingo.home')"
                    >
                        Nueva partida
                    </Link>
                    <button
                        class="rounded-lg bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                        type="button"
                        @click="shareGame"
                    >
                        Compartir
                    </button>
                    <button
                        class="rounded-lg bg-rose-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="state.loading.close || state.status === 'closed'"
                        type="button"
                        @click="closeGame"
                    >
                        {{ state.loading.close ? 'Cerrando...' : 'Cerrar' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="space-y-4 lg:col-span-2">
                        <NumberGrid :drawn-numbers="state.drawnNumbers" :max-number="props.game.maxNumber" />

                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex flex-wrap items-center gap-3">
                                <button
                                    class="rounded-lg bg-emerald-600 mx-auto px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="state.loading.draw || !canDraw"
                                    type="button"
                                    @click="drawNumber"
                                >
                                    {{ state.loading.draw ? 'Sacando...' : 'Sacar número' }}
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <BingoCardDisplay
                                v-for="card in state.cards"
                                :key="card.id"
                                :card-number="card.cardNumber"
                                :numbers-grid="card.numbersGrid"
                                :drawn-numbers="state.drawnNumbers"
                            />
                            <div v-if="state.cards.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                No hay tableros activos. Genera algunos para comenzar.
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="space-x-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <button
                                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="!canGenerateCards"
                                type="button"
                                @click="openGenerateModal"
                            >
                                Generar tableros
                            </button>
                        </div>
                        <PlayersList :players="state.players" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <CreateCardsModal
        :show="showGenerateModal"
        :loading="state.loading.generate"
        :can-generate="canGenerateCards"
        :max-cards-per-user="maxCardsPerUser"
        :current-cards-count="state.cards.length"
        @close="showGenerateModal = false"
        @generate="generateCards"
    />

    <NotificationCenter />
</template>
