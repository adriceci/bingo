import { ref } from 'vue';

const notifications = ref([]);
let counter = 0;

export function useNotifications() {
    const dismiss = (id) => {
        notifications.value = notifications.value.filter((note) => note.id !== id);
    };

    return {
        notifications,
        notify,
        notifyError,
        dismiss,
    };
}

export function notify(message, type = 'info', timeout = 4500) {
    const id = ++counter;
    notifications.value.push({ id, message, type });

    if (timeout) {
        setTimeout(() => {
            notifications.value = notifications.value.filter((note) => note.id !== id);
        }, timeout);
    }
}

export function notifyError(error, fallback = 'Ha ocurrido un error') {
    const message =
        typeof error === 'string'
            ? error
            : error?.response?.data?.message ?? error?.message ?? fallback;

    notify(message, 'error');
}
