<script setup>
import { computed, ref } from 'vue';
import VariantSelect from './VariantSelect.vue';
import { money } from '../../shared/format';

const props = defineProps({
    suppliers: { type: Array, default: () => [] },
    variants: { type: Array, default: () => [] },
    old: { type: Object, default: () => ({}) },
    errors: { type: Object, default: () => ({}) },
    today: { type: String, required: true },
});

const supplierId = ref(props.old.supplier_id ?? '');
const variantId = ref(props.old.product_variant_id ?? null);
const purchasePrice = ref(props.old.purchase_price ?? '');
const quantity = ref(props.old.quantity ?? '');
const purchaseDate = ref(props.old.purchase_date ?? props.today);
const notes = ref(props.old.notes ?? '');

const selectedVariant = computed(
    () => props.variants.find((variant) => String(variant.id) === String(variantId.value)) || null
);

const total = computed(() => (Number(purchasePrice.value) || 0) * (Number(quantity.value) || 0));

const stockAfter = computed(() => {
    if (!selectedVariant.value) return null;

    return selectedVariant.value.stock + (Number(quantity.value) || 0);
});

/** The cost price on the variant is overwritten by this purchase — worth showing. */
const costPriceChange = computed(() => {
    if (!selectedVariant.value || purchasePrice.value === '') return null;

    const current = selectedVariant.value.cost_price;
    const next = Number(purchasePrice.value) || 0;

    if (current === null || Number(current) === next) return null;

    return { from: current, to: next };
});

function error(field) {
    const messages = props.errors[field];

    return Array.isArray(messages) && messages.length ? messages[0] : null;
}
</script>

<template>
    <div class="row g-3">
        <div class="col-md-12">
            <label class="form-label fw-bold text-dark mb-1">Select Supplier</label>
            <select v-model="supplierId" name="supplier_id" class="form-select" :class="{ 'is-invalid': error('supplier_id') }" required>
                <option value="">Select Supplier</option>
                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                    {{ supplier.name }}
                </option>
            </select>
            <div v-if="error('supplier_id')" class="invalid-feedback d-block">{{ error('supplier_id') }}</div>
        </div>

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
            <label class="form-label fw-bold text-dark mb-1">Purchase Price (per unit)</label>
            <div class="input-group">
                <span class="input-group-text">৳</span>
                <input
                    v-model="purchasePrice"
                    type="number"
                    step="0.01"
                    min="0"
                    name="purchase_price"
                    class="form-control"
                    :class="{ 'is-invalid': error('purchase_price') }"
                    placeholder="0.00"
                    required
                >
            </div>
            <div v-if="error('purchase_price')" class="invalid-feedback d-block">{{ error('purchase_price') }}</div>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-bold text-dark mb-1">Quantity Purchased</label>
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
            <div class="border rounded p-3 bg-light">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted small">Total purchase cost</span>
                    <span class="fw-bold" style="color:#3d8202;">{{ money(total) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted small">Stock after this purchase</span>
                    <span class="fw-bold">{{ selectedVariant.stock }} → {{ stockAfter }}</span>
                </div>
                <div v-if="costPriceChange" class="d-flex justify-content-between">
                    <span class="text-muted small">Cost price will be updated</span>
                    <span class="small">{{ money(costPriceChange.from) }} → {{ money(costPriceChange.to) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <label class="form-label fw-bold text-dark mb-1">Purchase Date</label>
            <input
                v-model="purchaseDate"
                type="date"
                name="purchase_date"
                class="form-control"
                :class="{ 'is-invalid': error('purchase_date') }"
                required
            >
            <div v-if="error('purchase_date')" class="invalid-feedback d-block">{{ error('purchase_date') }}</div>
        </div>

        <div class="col-md-12">
            <label class="form-label fw-bold text-dark mb-1">Notes (Optional)</label>
            <textarea v-model="notes" name="notes" class="form-control" rows="3" placeholder="Additional details…"></textarea>
        </div>
    </div>
</template>
