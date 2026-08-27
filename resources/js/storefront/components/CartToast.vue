<script setup>
import { dismiss, toasts } from '../cart';

const TONE = {
    success: 'bg-success',
    danger: 'bg-danger',
    warning: 'bg-warning text-dark',
    info: 'bg-info text-dark',
};

function tone(type) {
    return TONE[type] ?? TONE.success;
}
</script>

<template>
    <div class="position-fixed bottom-0 end-0 p-3 d-flex flex-column gap-2" style="z-index:9999;">
        <TransitionGroup name="toast">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="toast show align-items-center text-white border-0"
                :class="tone(toast.type)"
                role="alert"
            >
                <div class="d-flex">
                    <div class="toast-body">{{ toast.message }}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" @click="dismiss(toast.id)"></button>
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: opacity 0.25s ease, transform 0.25s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(12px);
}
</style>
