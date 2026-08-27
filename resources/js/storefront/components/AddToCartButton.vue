<script setup>
import { ref } from 'vue';
import { addToCart } from '../cart';

const props = defineProps({
    productId: { type: [Number, String], required: true },
    variantId: { type: [Number, String], required: true },
    label: { type: String, default: 'Add to Cart' },
    icon: { type: String, default: 'bi-cart-plus' },
    buttonClass: { type: String, default: 'btn-add-cart' },
    quantity: { type: Number, default: 1 },
});

const busy = ref(false);

async function submit() {
    busy.value = true;
    await addToCart(props.productId, props.variantId, props.quantity);
    busy.value = false;
}
</script>

<template>
    <button type="button" :class="buttonClass" :disabled="busy" @click="submit">
        <span v-if="busy" class="spinner-border spinner-border-sm me-1"></span>
        <i v-else class="bi" :class="icon"></i>
        {{ label }}
    </button>
</template>
