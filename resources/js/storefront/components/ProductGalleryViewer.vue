<script setup>
import { ref } from 'vue';

const props = defineProps({
    images: { type: Array, default: () => [] },
    alt: { type: String, default: '' },
});

const current = ref(props.images[0] ?? null);
</script>

<template>
    <div>
        <div class="product-gallery-main">
            <img v-if="current" :src="current" :alt="alt">
        </div>

        <!-- One photo needs no picker. -->
        <div v-if="images.length > 1" class="d-flex gap-2 mt-3 flex-wrap">
            <button
                v-for="(image, index) in images"
                :key="index"
                type="button"
                class="gallery-thumb"
                :class="{ active: image === current }"
                @click="current = image"
                @mouseenter="current = image"
            >
                <img :src="image" :alt="`${alt} ${index + 1}`">
            </button>
        </div>
    </div>
</template>

<style scoped>
.gallery-thumb {
    width: 72px;
    height: 72px;
    padding: 2px;
    border: 2px solid transparent;
    border-radius: 10px;
    background: #fff;
    cursor: pointer;
    transition: border-color 0.2s ease;
}

.gallery-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 8px;
}

.gallery-thumb.active,
.gallery-thumb:hover {
    border-color: #2d6a4f;
}
</style>
