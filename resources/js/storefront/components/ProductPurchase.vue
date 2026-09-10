<script setup>
import { computed, ref } from 'vue';
import { addToCart, notify } from '../cart';
import { money } from '../../shared/format';
import PreorderDialog from './PreorderDialog.vue';

const props = defineProps({
    productId: { type: [Number, String], required: true },
    productName: { type: String, required: true },
    variants: { type: Array, default: () => [] },
    whatsappNumber: { type: String, default: '' },
    /** Placeholders: {product}, {variant}, {quantity} */
    whatsappTemplate: { type: String, default: '' },
    maxQuantity: { type: Number, default: 20 },
    /** The admin marked this product pre-orderable; each variant still has to
     *  be out of stock for the offer to apply to it. */
    preorderEnabled: { type: Boolean, default: false },
    preorderNote: { type: String, default: '' },
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

/** Sold out, but this one may still be taken as a pre-order. */
const isPreorder = computed(() => !!selected.value && !inStock.value && props.preorderEnabled);

/** Nothing to sell and no pre-order either: the button is simply off. */
const canBuy = computed(() => inStock.value || isPreorder.value);

const buyLabel = computed(() => {
    if (inStock.value) return label('addToCart', 'Add to Cart');

    return isPreorder.value ? label('preorder', 'Pre-order') : label('outOfStock', 'Out of Stock');
});

const buyIcon = computed(() => {
    if (inStock.value) return 'bi-cart-plus';

    return isPreorder.value ? 'bi-clock-history' : 'bi-x-circle';
});

const askingTerms = ref(false);

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

async function send() {
    busy.value = true;
    await addToCart(props.productId, selectedId.value, quantity.value);
    busy.value = false;
    askingTerms.value = false;
}

function submit() {
    if (!selectedId.value) {
        notify(label('selectOption', 'Please select an option'), 'warning');
        return;
    }

    // A pre-order stops for its terms; an ordinary line goes straight in.
    if (isPreorder.value) {
        askingTerms.value = true;
        return;
    }

    send();
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
                    <span v-if="variant.stock <= 0 && preorderEnabled" class="variant-preorder">
                        {{ label('preorderShort', 'Pre-order') }}
                    </span>
                    <span v-else-if="variant.stock <= 0" class="variant-soldout">
                        {{ label('outOfStock', 'Out of Stock') }}
                    </span>
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
            <button
                type="button"
                class="btn-primary-custom"
                :class="{ 'is-preorder': isPreorder }"
                :disabled="!canBuy || busy"
                @click="submit"
            >
                <span v-if="busy" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi" :class="buyIcon"></i>
                {{ buyLabel }}
            </button>
            <a :href="whatsappHref" class="btn-whatsapp" target="_blank" rel="noopener">
                <i class="bi bi-whatsapp"></i> {{ label('whatsapp', 'Order via WhatsApp') }}
            </a>
        </div>

        <PreorderDialog
            :open="askingTerms"
            :note="preorderNote"
            :busy="busy"
            :labels="labels"
            @confirm="send"
            @close="askingTerms = false"
        />
    </div>
</template>
