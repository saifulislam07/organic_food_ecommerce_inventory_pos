import { computed, reactive, readonly } from 'vue';
import http, { errorMessage } from '../shared/http';
import { config, route, t } from './config';

/**
 * One reactive cart shared by every storefront island — the badge in the navbar,
 * the add-to-cart buttons and the cart page all read and write this object.
 */
const state = reactive({
    count: 0,
    items: {},
    subtotal: 0,
    delivery: 0,
    total: 0,
    busy: false,
});

export const cart = readonly(state);

export const lines = computed(() =>
    Object.entries(state.items).map(([key, item]) => ({ key, ...item }))
);

export const amountToFreeDelivery = computed(() =>
    Math.max(0, config.freeDeliveryThreshold - state.subtotal)
);

/* ------------------------------------------------------------------ toasts */

let toastId = 0;
export const toasts = reactive([]);

export function notify(message, type = 'success') {
    const id = ++toastId;

    toasts.push({ id, message, type });

    setTimeout(() => dismiss(id), 4000);
}

export function dismiss(id) {
    const index = toasts.findIndex((toast) => toast.id === id);

    if (index !== -1) toasts.splice(index, 1);
}

/* ------------------------------------------------------------------ actions */

/** Seed the store from a page that already rendered the cart server-side. */
export function hydrate({ items = {}, subtotal = 0, delivery = 0, total = 0, count = null } = {}) {
    state.items = items;
    state.subtotal = Number(subtotal) || 0;
    state.delivery = Number(delivery) || 0;
    state.total = Number(total) || 0;
    state.count = count ?? countOf(items);
}

function countOf(items) {
    return Object.values(items).reduce((sum, item) => sum + Number(item.quantity || 0), 0);
}

function applyTotals(data) {
    if (data.items !== undefined) state.items = data.items;
    if (data.subtotal !== undefined) state.subtotal = Number(data.subtotal) || 0;
    if (data.delivery !== undefined) state.delivery = Number(data.delivery) || 0;
    if (data.total !== undefined) state.total = Number(data.total) || 0;

    state.count = data.cart_count ?? countOf(state.items);
}

export async function refreshCount() {
    const url = route('count');

    if (!url) return;

    try {
        const { data } = await http.get(url);
        state.count = Number(data.count) || 0;
    } catch {
        // A stale badge is not worth bothering the shopper about.
    }
}

export async function addToCart(productId, variantId, quantity = 1) {
    const url = route('add');

    if (!url) return false;

    state.busy = true;

    try {
        const { data } = await http.post(url, {
            product_id: productId,
            variant_id: variantId,
            quantity,
        });

        if (!data.success) {
            notify(data.message || t('error', 'Something went wrong!'), 'danger');
            return false;
        }

        state.count = data.cart_count ?? state.count + quantity;
        notify(t('added', 'Added to cart!'));

        return true;
    } catch (error) {
        notify(errorMessage(error, t('error', 'Something went wrong!')), 'danger');
        return false;
    } finally {
        state.busy = false;
    }
}

export async function updateQuantity(key, quantity) {
    if (quantity < 1) return removeItem(key);

    const url = route('update');

    if (!url) return false;

    state.busy = true;

    try {
        const { data } = await http.post(url, { key, quantity });

        if (!data.success) {
            notify(data.message || t('error', 'Something went wrong!'), 'danger');
            return false;
        }

        applyTotals(data);

        return true;
    } catch (error) {
        notify(errorMessage(error, t('error', 'Something went wrong!')), 'danger');
        return false;
    } finally {
        state.busy = false;
    }
}

export async function removeItem(key) {
    const url = route('remove');

    if (!url) return false;

    state.busy = true;

    try {
        const { data } = await http.post(url, { key });

        if (!data.success) {
            notify(data.message || t('error', 'Something went wrong!'), 'danger');
            return false;
        }

        applyTotals(data);
        notify(t('removed', 'Item removed'));

        return true;
    } catch (error) {
        notify(errorMessage(error, t('error', 'Something went wrong!')), 'danger');
        return false;
    } finally {
        state.busy = false;
    }
}
