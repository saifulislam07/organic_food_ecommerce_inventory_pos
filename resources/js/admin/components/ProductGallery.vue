<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';

/**
 * Up to `max` product photos, one of them the thumbnail.
 *
 * Everything posts with the surrounding Blade form: files as images[],
 * deletions as remove_images[], and the chosen thumbnail as thumbnail_id.
 */
const props = defineProps({
    existing: { type: Array, default: () => [] },
    max: { type: Number, default: 5 },
    thumbnailId: { type: [Number, String, null], default: null },
    error: { type: String, default: null },
});

const kept = ref(props.existing.map((image) => ({ ...image, remove: false })));
const chosenThumbnail = ref(props.thumbnailId);
const added = ref([]); // { file, url }
const fileInput = ref(null);

const keptCount = computed(() => kept.value.filter((image) => !image.remove).length);
const total = computed(() => keptCount.value + added.value.length);
const remaining = computed(() => Math.max(0, props.max - total.value));

function onFiles(event) {
    for (const file of Array.from(event.target.files)) {
        if (added.value.length + keptCount.value >= props.max) break;

        added.value.push({ file, url: URL.createObjectURL(file) });
    }

    // A pick replaces whatever the input held, so the running set is written
    // back over it. Clearing the input here instead would throw the files away
    // — it is the same element, and value = '' empties its FileList.
    syncInput();
}

function syncInput() {
    const transfer = new DataTransfer();

    added.value.forEach((item) => transfer.items.add(item.file));

    if (fileInput.value) fileInput.value.files = transfer.files;
}

function dropNew(index) {
    URL.revokeObjectURL(added.value[index].url);
    added.value.splice(index, 1);
    syncInput();
}

function toggleRemove(image) {
    image.remove = !image.remove;

    // Removing the thumbnail hands the badge to the first survivor.
    if (image.remove && String(chosenThumbnail.value) === String(image.id)) {
        chosenThumbnail.value = kept.value.find((other) => !other.remove)?.id ?? null;
    }
}

onBeforeUnmount(() => added.value.forEach((item) => URL.revokeObjectURL(item.url)));
</script>

<template>
    <div>
        <div v-if="error" class="alert alert-danger py-2 small">
            <i class="bi bi-exclamation-triangle-fill"></i> {{ error }}
        </div>

        <div v-if="kept.length || added.length" class="row g-2 mb-3">
            <div v-for="image in kept" :key="image.id" class="col-4">
                <div class="border rounded p-1 h-100 position-relative"
                     :class="{ 'opacity-25': image.remove, 'border-primary border-2': String(chosenThumbnail) === String(image.id) && !image.remove }">
                    <img :src="image.url" alt="" class="w-100 rounded" style="height:90px;object-fit:cover;">

                    <input v-if="image.remove" type="hidden" name="remove_images[]" :value="image.id">

                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <button type="button"
                                class="btn btn-sm p-0 border-0 bg-transparent small"
                                :class="String(chosenThumbnail) === String(image.id) ? 'text-primary fw-bold' : 'text-muted'"
                                :disabled="image.remove"
                                @click="chosenThumbnail = image.id">
                            <i class="bi" :class="String(chosenThumbnail) === String(image.id) ? 'bi-star-fill' : 'bi-star'"></i>
                            Thumbnail
                        </button>
                        <button type="button" class="btn btn-sm p-0 border-0 bg-transparent"
                                :class="image.remove ? 'text-secondary' : 'text-danger'"
                                @click="toggleRemove(image)">
                            <i class="bi" :class="image.remove ? 'bi-arrow-counterclockwise' : 'bi-trash'"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div v-for="(item, index) in added" :key="`new-${index}`" class="col-4">
                <div class="border border-success rounded p-1 h-100">
                    <img :src="item.url" alt="" class="w-100 rounded" style="height:90px;object-fit:cover;">
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <span class="badge bg-success">New</span>
                        <button type="button" class="btn btn-sm p-0 border-0 bg-transparent text-danger" @click="dropNew(index)">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="thumbnail_id" :value="chosenThumbnail ?? ''">

        <!--
            Hidden rather than disabled once the slots are full: a disabled
            input posts nothing, which would drop the photos just picked.
        -->
        <input
            v-show="remaining > 0"
            ref="fileInput"
            type="file"
            name="images[]"
            multiple
            accept="image/*"
            class="form-control"
            @change="onFiles"
        >

        <div class="form-text">
            <template v-if="remaining > 0">
                {{ total }} of {{ max }} used — you can add {{ remaining }} more.
            </template>
            <template v-else>
                All {{ max }} slots are used. Remove one to add another.
            </template>
            Click the star to pick which photo is shown in listings.
        </div>
    </div>
</template>
