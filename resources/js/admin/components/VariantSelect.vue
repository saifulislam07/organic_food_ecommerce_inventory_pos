<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

/**
 * Searchable replacement for a long <select> of product variants. Keeps a
 * hidden input around so the surrounding Blade form still posts normally.
 */
const props = defineProps({
    modelValue: { type: [Number, String, null], default: null },
    variants: { type: Array, default: () => [] },
    name: { type: String, default: 'product_variant_id' },
    placeholder: { type: String, default: 'Search product or variant…' },
    invalid: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const query = ref('');
const root = ref(null);

const selected = computed(
    () => props.variants.find((variant) => String(variant.id) === String(props.modelValue)) || null
);

const matches = computed(() => {
    const needle = query.value.trim().toLowerCase();

    if (!needle) return props.variants;

    return props.variants.filter(
        (variant) =>
            variant.product_name.toLowerCase().includes(needle) ||
            variant.variant_name.toLowerCase().includes(needle) ||
            (variant.sku || '').toLowerCase().includes(needle)
    );
});

function choose(variant) {
    emit('update:modelValue', variant.id);
    query.value = '';
    open.value = false;
}

function clear() {
    emit('update:modelValue', null);
    query.value = '';
    open.value = true;
}

function onDocumentClick(event) {
    if (root.value && !root.value.contains(event.target)) {
        open.value = false;
    }
}

onMounted(() => document.addEventListener('click', onDocumentClick));
onBeforeUnmount(() => document.removeEventListener('click', onDocumentClick));
</script>

<template>
    <div ref="root" class="position-relative">
        <input type="hidden" :name="name" :value="modelValue ?? ''">

        <button
            v-if="selected"
            type="button"
            class="form-select text-start d-flex align-items-center gap-2"
            :class="{ 'is-invalid': invalid }"
            @click="clear"
        >
            <span class="fw-bold text-dark">{{ selected.product_name }}</span>
            <span class="text-muted">— {{ selected.variant_name }}</span>
            <span
                class="badge ms-auto me-3"
                :class="selected.stock < 5 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'"
            >Stock: {{ selected.stock }}</span>
        </button>

        <input
            v-else
            v-model="query"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': invalid }"
            :placeholder="placeholder"
            autocomplete="off"
            @focus="open = true"
        >

        <div v-if="open && !selected" class="list-group variant-select-menu mt-1">
            <div v-if="!matches.length" class="list-group-item text-muted text-center py-3">
                No matching variant
            </div>
            <button
                v-for="variant in matches"
                :key="variant.id"
                type="button"
                class="list-group-item list-group-item-action d-flex align-items-center gap-2"
                @click="choose(variant)"
            >
                <span class="fw-bold text-dark">{{ variant.product_name }}</span>
                <span class="text-muted small">— {{ variant.variant_name }}</span>
                <span
                    class="badge ms-auto"
                    :class="variant.stock < 5 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'"
                >{{ variant.stock }}</span>
            </button>
        </div>
    </div>
</template>

<style scoped>
.variant-select-menu {
    position: absolute;
    width: 100%;
    z-index: 1050;
    max-height: 320px;
    overflow-y: auto;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}
</style>
