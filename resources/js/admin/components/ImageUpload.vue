<script setup>
import { onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    currentUrl: { type: String, default: null },
    name: { type: String, default: 'image' },
    accept: { type: String, default: 'image/*' },
    maxKb: { type: Number, default: 2048 },
});

const input = ref(null);
const preview = ref(null);
const fileName = ref(null);
const error = ref(null);
let objectUrl = null;

function releasePreview() {
    if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
        objectUrl = null;
    }
}

function onChange(event) {
    const file = event.target.files?.[0];

    releasePreview();
    error.value = null;

    if (!file) {
        preview.value = null;
        fileName.value = null;
        return;
    }

    if (file.size / 1024 > props.maxKb) {
        error.value = `Image is ${Math.round(file.size / 1024)} KB — the limit is ${props.maxKb} KB.`;
        reset();
        return;
    }

    objectUrl = URL.createObjectURL(file);
    preview.value = objectUrl;
    fileName.value = file.name;
}

function reset() {
    releasePreview();

    if (input.value) input.value.value = '';

    preview.value = null;
    fileName.value = null;
}

onBeforeUnmount(releasePreview);
</script>

<template>
    <div>
        <div v-if="preview || currentUrl" class="mb-2 position-relative">
            <img
                :src="preview || currentUrl"
                alt="Product image"
                class="rounded border"
                style="max-width:100%;display:block;"
            >
            <span
                v-if="preview"
                class="badge bg-success position-absolute"
                style="top:8px;left:8px;"
            >New</span>
        </div>

        <input
            ref="input"
            type="file"
            :name="name"
            :accept="accept"
            class="form-control"
            :class="{ 'is-invalid': error }"
            @change="onChange"
        >

        <div v-if="error" class="invalid-feedback d-block">{{ error }}</div>

        <div v-if="fileName" class="d-flex align-items-center gap-2 mt-2 small text-muted">
            <i class="bi bi-paperclip"></i>
            <span class="text-truncate">{{ fileName }}</span>
            <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-auto" @click="reset">Remove</button>
        </div>
    </div>
</template>
