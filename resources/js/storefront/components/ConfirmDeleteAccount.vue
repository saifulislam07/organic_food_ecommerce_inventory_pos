<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

/**
 * Account deletion confirm dialog. The form posts normally — this only owns
 * showing and hiding the modal, so a failed password round-trips through Laravel
 * and reopens the dialog with its error.
 */
const props = defineProps({
    action: { type: String, required: true },
    /** Reopen straight away when the last attempt came back with an error. */
    open: { type: Boolean, default: false },
    error: { type: String, default: null },
    labels: { type: Object, default: () => ({}) },
});

const show = ref(props.open);
const passwordInput = ref(null);

const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content ?? '';

function label(key, fallback) {
    return props.labels[key] ?? fallback;
}

async function openModal() {
    show.value = true;
    await nextTick();
    passwordInput.value?.focus();
}

function closeModal() {
    show.value = false;
}

function onKeydown(event) {
    if (event.key === 'Escape' && show.value) closeModal();
}

onMounted(() => {
    document.addEventListener('keydown', onKeydown);

    if (props.open) {
        nextTick(() => passwordInput.value?.focus());
    }
});

onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <div>
        <p class="text-muted small mb-4">{{ label('warning', '') }}</p>

        <button type="button" class="btn btn-danger fw-bold" @click="openModal">
            <i class="bi bi-trash"></i> {{ label('trigger', 'Delete Account') }}
        </button>

        <Teleport to="body">
            <div v-if="show">
                <div class="modal fade show d-block" tabindex="-1" @click.self="closeModal">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg">
                            <form :action="action" method="post" class="p-4">
                                <input type="hidden" name="_token" :value="csrfToken">
                                <input type="hidden" name="_method" value="delete">

                                <h4 class="fw-bold mb-3 text-dark">{{ label('title', 'Are you sure?') }}</h4>

                                <p class="text-muted small mb-4">{{ label('body', '') }}</p>

                                <div class="mb-4">
                                    <label for="delete-account-password" class="form-label fw-bold small text-uppercase">
                                        {{ label('password', 'Password') }}
                                    </label>
                                    <input
                                        id="delete-account-password"
                                        ref="passwordInput"
                                        type="password"
                                        name="password"
                                        class="form-control"
                                        :class="{ 'is-invalid': error }"
                                        :placeholder="label('passwordPlaceholder', 'Password')"
                                        autocomplete="current-password"
                                    >
                                    <div v-if="error" class="invalid-feedback d-block">{{ error }}</div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-light fw-bold" @click="closeModal">
                                        {{ label('cancel', 'Cancel') }}
                                    </button>
                                    <button type="submit" class="btn btn-danger fw-bold">
                                        {{ label('confirm', 'Delete Account') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-backdrop fade show"></div>
            </div>
        </Teleport>
    </div>
</template>
