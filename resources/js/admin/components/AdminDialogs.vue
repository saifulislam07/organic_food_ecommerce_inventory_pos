<script setup>
import { computed, onBeforeUnmount, onMounted } from 'vue';
import { confirmState, dismissToast, resolveConfirm, toasts } from '../ui';

/**
 * The one island every admin page carries: the toast stack and the confirm
 * dialog. Styled from the panel's own palette rather than a dialog library.
 */

const TONES = {
    danger: { icon: 'bi-trash3', ring: 'tone-danger', button: 'btn-danger' },
    warning: { icon: 'bi-exclamation-triangle', ring: 'tone-warning', button: 'btn-warning' },
    primary: { icon: 'bi-question-lg', ring: 'tone-primary', button: 'btn-primary' },
};

const tone = computed(() => TONES[confirmState.tone] ?? TONES.danger);

const TOAST_TONES = {
    success: { icon: 'bi-check-circle-fill', css: 'toast-success' },
    danger: { icon: 'bi-exclamation-octagon-fill', css: 'toast-danger' },
    warning: { icon: 'bi-exclamation-triangle-fill', css: 'toast-warning' },
    info: { icon: 'bi-info-circle-fill', css: 'toast-info' },
};

function toastTone(type) {
    return TOAST_TONES[type] ?? TOAST_TONES.success;
}

function onKeydown(event) {
    if (event.key === 'Escape' && confirmState.open) resolveConfirm(false);
    if (event.key === 'Enter' && confirmState.open) resolveConfirm(true);
}

onMounted(() => document.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <div>
        <!-- Toasts -->
        <div class="admin-toasts">
            <TransitionGroup name="toast">
                <div v-for="item in toasts" :key="item.id" class="admin-toast" :class="toastTone(item.type).css">
                    <i class="bi" :class="toastTone(item.type).icon"></i>
                    <span class="flex-grow-1">{{ item.message }}</span>
                    <button type="button" class="admin-toast-close" @click="dismissToast(item.id)">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </TransitionGroup>
        </div>

        <!-- Confirm -->
        <Transition name="dialog">
            <div v-if="confirmState.open" class="admin-dialog-backdrop" @click.self="resolveConfirm(false)">
                <div class="admin-dialog" role="alertdialog" aria-modal="true">
                    <div class="admin-dialog-icon" :class="tone.ring">
                        <i class="bi" :class="tone.icon"></i>
                    </div>

                    <h5 class="fw-bold text-dark mb-2">{{ confirmState.title }}</h5>
                    <p v-if="confirmState.message" class="text-muted mb-4">{{ confirmState.message }}</p>

                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-light px-4" @click="resolveConfirm(false)">
                            Cancel
                        </button>
                        <button type="button" class="btn px-4" :class="tone.button" @click="resolveConfirm(true)">
                            {{ confirmState.confirmLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
/* ------------------------------------------------------------------ toasts */
.admin-toasts {
    position: fixed;
    top: 24px;
    right: 24px;
    z-index: 2000;
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-width: min(380px, calc(100vw - 48px));
}

.admin-toast {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
    border-left: 4px solid;
    font-weight: 500;
}

.admin-toast i:first-child { font-size: 1.15rem; }

/* The panel's own greens and reds, not Bootstrap's defaults. */
.toast-success { border-left-color: #3d8202; color: #1e4a01; }
.toast-success i:first-child { color: #3d8202; }
.toast-danger { border-left-color: #c1121f; color: #6a040f; }
.toast-danger i:first-child { color: #c1121f; }
.toast-warning { border-left-color: #fda102; color: #6d4a02; }
.toast-warning i:first-child { color: #b85600; }
.toast-info { border-left-color: #457b9d; color: #1d3557; }
.toast-info i:first-child { color: #457b9d; }

.admin-toast-close {
    border: 0;
    background: none;
    color: inherit;
    opacity: 0.45;
    padding: 0;
    line-height: 1;
}

.admin-toast-close:hover { opacity: 1; }

.toast-enter-active, .toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(24px); }

/* ------------------------------------------------------------------ dialog */
.admin-dialog-backdrop {
    position: fixed;
    inset: 0;
    z-index: 2100;
    background: rgba(13, 27, 42, 0.55);
    backdrop-filter: blur(2px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.admin-dialog {
    background: #fff;
    border-radius: 18px;
    padding: 32px 28px 26px;
    width: 100%;
    max-width: 420px;
    text-align: center;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.25);
}

.admin-dialog-icon {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 18px;
    font-size: 1.8rem;
}

.tone-danger { background: #fde8e9; color: #c1121f; }
.tone-warning { background: #fef2d9; color: #b85600; }
.tone-primary { background: #eaf4dc; color: #3d8202; }

.dialog-enter-active, .dialog-leave-active { transition: opacity 0.2s ease; }
.dialog-enter-from, .dialog-leave-to { opacity: 0; }
.dialog-enter-active .admin-dialog { animation: pop 0.22s ease; }

@keyframes pop {
    from { transform: scale(0.92); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

@media (prefers-reduced-motion: reduce) {
    .dialog-enter-active .admin-dialog { animation: none; }
}
</style>
