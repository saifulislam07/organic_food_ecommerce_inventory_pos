<script setup>
import { computed, ref } from 'vue';
import { addToCart, notify } from '../cart';
import { money } from '../../shared/format';

const props = defineProps({
    productId: { type: [Number, String], required: true },
    productName: { type: String, required: true },
    variants: { type: Array, default: () => [] },
    whatsappNumber: { type: String, default: '' },
    /** Placeholders: {product}, {variant}, {quantity} */
    whatsappTemplate: { type: String, default: '' },
    maxQuantity: { type: Number, default: 20 },
    labels: { type: Object, default: () => ({}) },
});

const selectedId = ref(props.variants[0]?.id ?? null);
const quantity = ref(1);
const busy = ref(false);

const selected = computed(
    () => props.variants.find((variant) => variant.id === selectedId.value) || null
);

const onSale = computed(
    () => !!selected.value && selected.value.sale_price !== null && selected.value.sale_price < selected.value.price
);

const discount = computed(() =>
    onSale.value
        ? Math.round(((selected.value.price - selected.value.sale_price) / selected.value.price) * 100)
        : 0
);

const inStock = computed(() => !!selected.value && selected.value.stock > 0);

const whatsappHref = computed(() => {
    if (!props.whatsappNumber) return '#';

    const text = props.whatsappTemplate
        .replace('{product}', props.productName)
        .replace('{variant}', selected.value?.name ?? '')
        .replace('{quantity}', String(quantity.value));

    return `https://wa.me/${props.whatsappNumber}?text=${encodeURIComponent(text)}`;
});

function label(key, fallback) {
    return props.labels[key] ?? fallback;
}

function changeQty(delta) {
    const next = quantity.value + delta;

    quantity.value = Math.min(Math.max(next, 1), props.maxQuantity);
}

function setQty(value) {
    const next = Math.floor(Number(value));

    quantity.value = Number.isFinite(next) ? Math.min(Math.max(next, 1), props.maxQuantity) : 1;
}

async function submit() {
    if (!selectedId.value) {
        notify(label('selectOption', 'Please select an option'), 'warning');
        return;
    }

    busy.value = true;
    await addToCart(props.productId, selectedId.value, quantity.value);
    busy.value = false;
}
</script>

<template>
    <div>
        <div v-if="selected" class="product-detail-price">
            <span class="price-now">{{ money(selected.display_price) }}</span>
            <template v-if="onSale">
                <span class="price-original">{{ money(selected.price) }}</span>
                <span class="price-off">{{ discount }}% {{ label('discount', 'off') }}</span>
            </template>
        </div>

        <div v-if="variants.length" class="mb-3">
            <label class="form-label fw-bold">{{ label('selectLabel', 'Select Option:') }}</label>
            <div class="variant-options">
                <button
                    v-for="variant in variants"
                    :key="variant.id"
                    type="button"
                    class="variant-btn"
                    :class="{ active: variant.id === selectedId }"
                    @click="selectedId = variant.id"
                >
                    <span class="variant-name">{{ variant.name }}</span>
                    <span class="variant-price">{{ money(variant.display_price) }}</span>
                </button>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">{{ label('quantityLabel', 'Quantity:') }}</label>
            <div class="qty-control">
                <button type="button" class="qty-btn" @click="changeQty(-1)">−</button>
                <input
                    type="number"
                    class="qty-value"
                    :value="quantity"
                    min="1"
                    :max="maxQuantity"
                    @change="setQty($event.target.value)"
                >
                <button type="button" class="qty-btn" @click="changeQty(1)">+</button>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-3 mb-4">
            <button type="button" class="btn-primary-custom" :disabled="!inStock || busy" @click="submit">
                <span v-if="busy" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi" :class="inStock ? 'bi-cart-plus' : 'bi-x-circle'"></i>
                {{ inStock ? label('addToCart', 'Add to Cart') : label('outOfStock', 'Out of Stock') }}
            </button>
            <a :href="whatsappHref" class="btn-whatsapp" target="_blank" rel="noopener">
                <i class="bi bi-whatsapp"></i> {{ label('whatsapp', 'Order via WhatsApp') }}
            </a>
        </div>
    </div>
</template>
