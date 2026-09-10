<script setup>
import { ref } from 'vue';
import { addToCart } from '../cart';
import PreorderDialog from './PreorderDialog.vue';

const props = defineProps({
    productId: { type: [Number, String], required: true },
    variantId: { type: [Number, String], required: true },
    label: { type: String, default: 'Add to Cart' },
    icon: { type: String, default: 'bi-cart-plus' },
    buttonClass: { type: String, default: 'btn-add-cart' },
    quantity: { type: Number, default: 1 },
    /** Sold out, but the admin allows it to be taken as a pre-order. */
    preorder: { type: Boolean, default: false },
    preorderNote: { type: String, default: '' },
    preorderLabels: { type: Object, default: () => ({}) },
});

const busy = ref(false);
const askingTerms = ref(false);

async function send() {
    busy.value = true;
    await addToCart(props.productId, props.variantId, props.quantity);
    busy.value = false;
    askingTerms.value = false;
}

/** A pre-order stops for its terms; an ordinary line goes straight in. */
function submit() {
    if (props.preorder) {
        askingTerms.value = true;
        return;
    }

    send();
}
</script>

<template>
    <button
        type="button"
        :class="[buttonClass, { 'is-preorder': preorder }]"
        :disabled="busy"
        @click="submit"
    >
        <span v-if="busy" class="spinner-border spinner-border-sm me-1"></span>
        <i v-else class="bi" :class="preorder ? 'bi-clock-history' : icon"></i>
        {{ label }}
    </button>

    <PreorderDialog
        :open="askingTerms"
        :note="preorderNote"
        :busy="busy"
        :labels="preorderLabels"
        @confirm="send"
        @close="askingTerms = false"
    />
</template>
