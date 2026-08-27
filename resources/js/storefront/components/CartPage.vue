<script setup>
import { computed, onMounted, ref } from 'vue';
import { amountToFreeDelivery, cart, hydrate, lines, removeItem, updateQuantity } from '../cart';
import { money } from '../../shared/format';

const props = defineProps({
    items: { type: Object, default: () => ({}) },
    subtotal: { type: Number, default: 0 },
    delivery: { type: Number, default: 0 },
    total: { type: Number, default: 0 },
    shopUrl: { type: String, required: true },
    checkoutUrl: { type: String, required: true },
    labels: { type: Object, default: () => ({}) },
});

/** Row keys currently mid-request, so only that row shows a spinner. */
const pending = ref(new Set());

onMounted(() => {
    hydrate({
        items: props.items,
        subtotal: props.subtotal,
        delivery: props.delivery,
        total: props.total,
    });
});

const isEmpty = computed(() => lines.value.length === 0);

function label(key, fallback) {
    return props.labels[key] ?? fallback;
}

function busy(key) {
    return pending.value.has(key);
}

async function withPending(key, action) {
    pending.value = new Set(pending.value).add(key);

    try {
        await action();
    } finally {
        const next = new Set(pending.value);
        next.delete(key);
        pending.value = next;
    }
}

function changeQty(line, delta) {
    return withPending(line.key, () => updateQuantity(line.key, line.quantity + delta));
}

function setQty(line, value) {
    const next = Math.floor(Number(value));

    if (!Number.isFinite(next) || next === line.quantity) return;

    return withPending(line.key, () => updateQuantity(line.key, next));
}

function remove(line) {
    return withPending(line.key, () => removeItem(line.key));
}
</script>

<template>
    <div v-if="isEmpty" class="empty-state">
        <div class="empty-state-icon"><i class="bi bi-cart-x"></i></div>
        <h3>{{ label('emptyTitle', 'Your cart is empty') }}</h3>
        <p class="text-muted">{{ label('emptyBody', "Looks like you haven't added anything yet.") }}</p>
        <a :href="shopUrl" class="btn-primary-custom mt-3">
            <i class="bi bi-shop"></i> {{ label('startShopping', 'Start Shopping') }}
        </a>
    </div>

    <div v-else class="row g-4">
        <div class="col-lg-8">
            <div class="table-responsive">
                <table class="table cart-table">
                    <thead>
                        <tr>
                            <th>{{ label('product', 'Product') }}</th>
                            <th>{{ label('price', 'Price') }}</th>
                            <th>{{ label('quantity', 'Quantity') }}</th>
                            <th>{{ label('subtotal', 'Subtotal') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="line in lines" :key="line.key" :style="{ opacity: busy(line.key) ? 0.5 : 1 }">
                            <td :data-label="label('product', 'Product')">
                                <div class="d-flex align-items-center gap-3">
                                    <img :src="line.image" :alt="line.product_name" class="cart-item-img">
                                    <div>
                                        <strong class="d-block">{{ line.product_name }}</strong>
                                        <small class="text-muted">{{ line.variant_name }}</small>
                                    </div>
                                </div>
                            </td>
                            <td :data-label="label('price', 'Price')">{{ money(line.price) }}</td>
                            <td :data-label="label('quantity', 'Quantity')">
                                <div class="qty-control">
                                    <button type="button" class="qty-btn" :disabled="busy(line.key)" @click="changeQty(line, -1)">−</button>
                                    <input
                                        type="number"
                                        class="qty-value"
                                        min="1"
                                        :value="line.quantity"
                                        :disabled="busy(line.key)"
                                        @change="setQty(line, $event.target.value)"
                                    >
                                    <button type="button" class="qty-btn" :disabled="busy(line.key)" @click="changeQty(line, 1)">+</button>
                                </div>
                            </td>
                            <td :data-label="label('subtotal', 'Subtotal')" class="fw-bold text-primary">
                                {{ money(line.price * line.quantity) }}
                            </td>
                            <td>
                                <button type="button" class="cart-remove" :disabled="busy(line.key)" :title="label('remove', 'Remove')" @click="remove(line)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <a :href="shopUrl" class="btn-primary-custom">
                <i class="bi bi-arrow-left"></i> {{ label('continue', 'Continue Shopping') }}
            </a>
        </div>

        <div class="col-lg-4">
            <div class="cart-summary">
                <h4><i class="bi bi-receipt"></i> {{ label('summary', 'Order Summary') }}</h4>
                <div class="summary-row">
                    <span>{{ label('subtotal', 'Subtotal') }}</span>
                    <span>{{ money(cart.subtotal) }}</span>
                </div>
                <div class="summary-row">
                    <span>{{ label('delivery', 'Delivery') }}</span>
                    <span>
                        <span v-if="cart.delivery === 0" class="free-delivery-badge">{{ label('free', 'FREE') }}</span>
                        <template v-else>{{ money(cart.delivery) }}</template>
                    </span>
                </div>
                <div class="summary-row total">
                    <span>{{ label('total', 'Total') }}</span>
                    <span>{{ money(cart.total) }}</span>
                </div>

                <div v-if="amountToFreeDelivery > 0" class="alert alert-info mt-3 mb-0" style="font-size:.85rem;">
                    <i class="bi bi-info-circle"></i>
                    {{ label('freeDeliveryHint', 'Order {amount} more for FREE delivery!').replace('{amount}', money(amountToFreeDelivery)) }}
                </div>

                <a :href="checkoutUrl" class="btn-primary-custom w-100 justify-content-center mt-3">
                    <i class="bi bi-lock"></i> {{ label('checkout', 'Proceed to Checkout') }}
                </a>
            </div>
        </div>
    </div>
</template>
