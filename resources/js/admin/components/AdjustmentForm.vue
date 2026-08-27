<script setup>
import { computed, ref } from 'vue';
import VariantSelect from './VariantSelect.vue';

const props = defineProps({
    variants: { type: Array, default: () => [] },
    old: { type: Object, default: () => ({}) },
    errors: { type: Object, default: () => ({}) },
    today: { type: String, required: true },
});

const TYPES = [
    { value: 'damage', label: 'Damage', direction: -1 },
    { value: 'lost', label: 'Lost', direction: -1 },
    { value: 'returned', label: 'Returned', direction: 1 },
    { value: 'other', label: 'Other', direction: -1 },
];

const variantId = ref(props.old.product_variant_id ?? null);
const type = ref(props.old.type ?? 'damage');
const quantity = ref(props.old.quantity ?? '');
const adjustmentDate = ref(props.old.adjustment_date ?? props.today);
const reason = ref(props.old.reason ?? '');

const selectedVariant = computed(
    () => props.variants.find((variant) => String(variant.id) === String(variantId.value)) || null
);

const direction = computed(() => TYPES.find((t) => t.value === type.value)?.direction ?? -1);

const stockAfter = computed(() => {
    if (!selectedVariant.value) return null;

    return selectedVariant.value.stock + direction.value * (Number(quantity.value) || 0);
});

const goesNegative = computed(() => stockAfter.value !== null && stockAfter.value < 0);

function error(field) {
    const messages = props.errors[field];

    return Array.isArray(messages) && messages.length ? messages[0] : null;
}
</script>

<template>
    <div class="row g-3">
        <div class="col-md-12">
            <label class="form-label fw-bold text-dark mb-1">Select Product Variant</label>
            <VariantSelect
                v-model="variantId"
                :variants="variants"
                name="product_variant_id"
                :invalid="!!error('product_variant_id')"
                placeholder="Type to search product or variant…"
            />
            <div v-if="error('product_variant_id')" class="invalid-feedback d-block">{{ error('product_variant_id') }}</div>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-bold text-dark mb-1">Adjustment Type</label>
            <select v-model="type" name="type" class="form-select" :class="{ 'is-invalid': error('type') }" required>
                <option v-for="option in TYPES" :key="option.value" :value="option.value">
                    {{ option.label }} ({{ option.direction > 0 ? 'Increases' : 'Decreases' }} Stock)
                </option>
            </select>
            <div v-if="error('type')" class="invalid-feedback d-block">{{ error('type') }}</div>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-bold text-dark mb-1">Quantity</label>
            <input
                v-model="quantity"
                type="number"
                min="1"
                name="quantity"
                class="form-control"
                :class="{ 'is-invalid': error('quantity') }"
                placeholder="Enter amount"
                required
            >
            <div v-if="error('quantity')" class="invalid-feedback d-block">{{ error('quantity') }}</div>
        </div>

        <div v-if="selectedVariant" class="col-md-12">
            <div class="border rounded p-3" :class="goesNegative ? 'bg-danger-subtle' : 'bg-light'">
                <div class="d-flex justify-content-between">
                    <span class="text-muted small">Stock after this adjustment</span>
                    <span class="fw-bold" :class="{ 'text-danger': goesNegative }">
                        {{ selectedVariant.stock }} → {{ stockAfter }}
                    </span>
                </div>
                <div v-if="goesNegative" class="small text-danger mt-2">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    This would push stock below zero.
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <label class="form-label fw-bold text-dark mb-1">Adjustment Date</label>
            <input
                v-model="adjustmentDate"
                type="date"
                name="adjustment_date"
                class="form-control"
                :class="{ 'is-invalid': error('adjustment_date') }"
                required
            >
            <div v-if="error('adjustment_date')" class="invalid-feedback d-block">{{ error('adjustment_date') }}</div>
        </div>

        <div class="col-md-12">
            <label class="form-label fw-bold text-dark mb-1">Reason/Notes</label>
            <textarea
                v-model="reason"
                name="reason"
                class="form-control"
                :class="{ 'is-invalid': error('reason') }"
                rows="3"
                placeholder="Explain why this adjustment is being made…"
                required
            ></textarea>
            <div v-if="error('reason')" class="invalid-feedback d-block">{{ error('reason') }}</div>
        </div>
    </div>
</template>
