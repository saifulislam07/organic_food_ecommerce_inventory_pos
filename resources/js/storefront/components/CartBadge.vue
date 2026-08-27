<script setup>
import { cart } from '../cart';

defineProps({
    extraClass: { type: String, default: '' },
});
</script>

<template>
    <span class="cart-badge" :class="[extraClass, { show: cart.count > 0 }]">{{ cart.count }}</span>
</template>
