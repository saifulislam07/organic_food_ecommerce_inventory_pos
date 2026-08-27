<script setup>
import { computed, ref } from 'vue';
import http, { errorMessage } from '../../shared/http';

const props = defineProps({
    current: { type: String, required: true },
    updateUrl: { type: String, required: true },
    updatedAt: { type: String, default: '' },
});

const STATUSES = [
    { value: 'pending', label: 'Pending', badge: 'bg-warning' },
    { value: 'confirmed', label: 'Confirmed', badge: 'bg-info' },
    { value: 'processing', label: 'Processing', badge: 'bg-primary' },
    { value: 'shipped', label: 'Shipped', badge: 'bg-secondary' },
    { value: 'delivered', label: 'Delivered', badge: 'bg-success' },
    { value: 'cancelled', label: 'Cancelled', badge: 'bg-danger' },
];

const status = ref(props.current);
const draft = ref(props.current);
const stamp = ref(props.updatedAt);
const saving = ref(false);
const saved = ref(false);
const error = ref(null);

const badge = computed(
    () => STATUSES.find((option) => option.value === status.value) || { label: 'Unknown', badge: 'bg-dark' }
);

const isDirty = computed(() => draft.value !== status.value);

async function save() {
    saving.value = true;
    error.value = null;
    saved.value = false;

    try {
        const { data } = await http.patch(props.updateUrl, { status: draft.value });

        status.value = data.status ?? draft.value;
        stamp.value = data.updated_at ?? stamp.value;
        saved.value = true;

        setTimeout(() => {
            saved.value = false;
        }, 2000);
    } catch (exception) {
        error.value = errorMessage(exception, 'Could not update the order status.');
        draft.value = status.value;
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div>
        <div class="mb-3 d-flex align-items-center gap-2 flex-wrap">
            <span class="badge" :class="badge.badge">{{ badge.label }}</span>
            <span v-if="stamp" class="text-muted small">Updated on {{ stamp }}</span>
            <span v-if="saved" class="text-success small ms-auto">
                <i class="bi bi-check-circle-fill me-1"></i>Saved
            </span>
        </div>

        <div class="input-group">
            <select v-model="draft" class="form-select" :disabled="saving">
                <option v-for="option in STATUSES" :key="option.value" :value="option.value">
                    {{ option.label }}
                </option>
            </select>
            <button type="button" class="btn btn-primary" :disabled="saving || !isDirty" @click="save">
                <span v-if="saving" class="spinner-border spinner-border-sm"></span>
                <span v-else>Update</span>
            </button>
        </div>

        <div v-if="error" class="text-danger small mt-2">{{ error }}</div>
    </div>
</template>
