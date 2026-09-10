<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

/**
 * The terms a shopper agrees to before a sold-out item goes in the cart.
 *
 * The confirm button stays disabled until the box is ticked, and the tick is
 * cleared every time the dialog opens — an agreement carried over from the last
 * product is not an agreement to this one's terms. The server checks it again
 * at checkout regardless; this is the part the shopper actually reads.
 */
const props = defineProps({
    open: { type: Boolean, default: false },
    /** The admin's note. Rendered as plain text across its own lines. */
    note: { type: String, default: '' },
    busy: { type: Boolean, default: false },
    labels: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['confirm', 'close']);

const accepted = ref(false);
const checkbox = ref(null);

function label(key, fallback) {
    return props.labels[key] ?? fallback;
}

function close() {
    if (props.busy) return;
    emit('close');
}

function confirm() {
    if (!accepted.value || props.busy) return;
    emit('confirm');
}

function onKeydown(event) {
    if (event.key === 'Escape' && props.open) close();
}

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;

        accepted.value = false;
        requestAnimationFrame(() => checkbox.value?.focus());
    }
);

onMounted(() => document.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Teleport to="body">
        <div v-if="open">
            <div class="modal fade show d-block" tabindex="-1" @click.self="close">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="p-4">
                            <h4 class="fw-bold mb-1 text-dark">
                                <i class="bi bi-clock-history text-warning me-1"></i>
                                {{ label('title', 'Pre-order conditions') }}
                            </h4>
                            <p class="text-muted small mb-3">
                                {{ label('intro', 'This item is out of stock. Please read before ordering.') }}
                            </p>

                            <div class="preorder-terms mb-3">{{ note }}</div>

                            <div class="form-check mb-4">
                                <input
                                    id="preorder-accept"
                                    ref="checkbox"
                                    v-model="accepted"
                                    class="form-check-input"
                                    type="checkbox"
                                >
                                <label class="form-check-label fw-semibold" for="preorder-accept">
                                    {{ label('accept', 'I have read and accept these conditions') }}
                                </label>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-light fw-bold" :disabled="busy" @click="close">
                                    {{ label('cancel', 'Cancel') }}
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-warning fw-bold"
                                    :disabled="!accepted || busy"
                                    @click="confirm"
                                >
                                    <span v-if="busy" class="spinner-border spinner-border-sm me-1"></span>
                                    {{ label('confirm', 'Pre-order') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-backdrop fade show"></div>
        </div>
    </Teleport>
</template>
