<script setup>
import { computed, ref, watch } from 'vue';
import VariantSelect from './VariantSelect.vue';
import { money } from '../../shared/format';

/**
 * Pick the products that go in a combo, watch what they add up to, then set the
 * price the shop actually charges. Everything posts with the surrounding form.
 */
const props = defineProps({
    options: { type: Array, default: () => [] },
    components: { type: Array, default: () => [] },
    price: { type: [Number, String, null], default: null },
    comparePrice: { type: [Number, String, null], default: null },
    error: { type: String, default: null },
});

const rows = ref(
    props.components.length
        ? props.components.map((c) => ({ variant_id: c.variant_id, quantity: c.quantity }))
        : [{ variant_id: null, quantity: 1 }]
);

/** Whether the shopkeeper has typed their own price yet. */
const priceTouched = ref(props.price !== null && props.price !== '');
const finalPrice = ref(props.price ?? '');

function optionFor(id) {
    return props.options.find((option) => String(option.id) === String(id)) || null;
}

function lineTotal(row) {
    const option = optionFor(row.variant_id);

    return option ? option.price * (Number(row.quantity) || 0) : 0;
}

/** What the parts would cost bought separately. */
const partsTotal = computed(() => rows.value.reduce((sum, row) => sum + lineTotal(row), 0));

/** Until the price is edited it simply tracks the parts total. */
watch(partsTotal, (value) => {
    if (!priceTouched.value) finalPrice.value = value ? Math.round(value) : '';
});

const savings = computed(() => partsTotal.value - (Number(finalPrice.value) || 0));

const savingsPercent = computed(() =>
    partsTotal.value > 0 ? Math.round((savings.value / partsTotal.value) * 100) : 0
);

const buildable = computed(() => {
    const chosen = rows.value.filter((row) => row.variant_id);

    if (!chosen.length) return null;

    return Math.min(
        ...chosen.map((row) => {
            const option = optionFor(row.variant_id);
            const quantity = Number(row.quantity) || 0;

            return option && quantity > 0 ? Math.floor(option.stock / quantity) : 0;
        })
    );
});

/**
 * A product already in the box is removed from every other row's list, so a
 * duplicate cannot be picked in the first place. Its own row keeps it, or the
 * select would have nothing to show for the current value.
 */
function optionsFor(index) {
    const taken = rows.value
        .filter((_, i) => i !== index)
        .map((row) => String(row.variant_id))
        .filter((id) => id !== 'null' && id !== '');

    return props.options.filter((option) => ! taken.includes(String(option.id)));
}

const remaining = computed(() => props.options.length - rows.value.filter((row) => row.variant_id).length);

function addRow() {
    rows.value.push({ variant_id: null, quantity: 1 });
}

function removeRow(index) {
    rows.value.splice(index, 1);

    if (!rows.value.length) addRow();
}

function resetPrice() {
    priceTouched.value = false;
    finalPrice.value = partsTotal.value ? Math.round(partsTotal.value) : '';
}
</script>

<template>
    <div>
        <div v-if="error" class="alert alert-danger py-2 small">
            <i class="bi bi-exclamation-triangle-fill"></i> {{ error }}
        </div>

        <!-- What goes in the box -->
        <div v-for="(row, index) in rows" :key="index" class="row g-2 align-items-end mb-2">
            <div class="col-md-6">
                <label class="form-label small text-muted">Product</label>
                <VariantSelect
                    v-model="row.variant_id"
                    :variants="optionsFor(index)"
                    :name="`components[${index}][variant_id]`"
                    placeholder="Search a product to add…"
                />
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Qty</label>
                <input
                    v-model.number="row.quantity"
                    type="number"
                    min="1"
                    :name="`components[${index}][quantity]`"
                    class="form-control"
                >
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Line total</label>
                <div class="form-control bg-light text-end fw-bold">{{ money(lineTotal(row)) }}</div>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger w-100" @click="removeRow(index)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>

        <button type="button" class="btn btn-sm btn-outline-success mt-1"
                :disabled="remaining < 1" @click="addRow">
            <i class="bi bi-plus-circle"></i> Add product
        </button>
        <span v-if="remaining < 1" class="text-muted small ms-2">
            Every available product is already in this combo.
        </span>

        <!-- Pricing -->
        <div class="row g-3 mt-4 pt-3 border-top">
            <div class="col-md-4">
                <label class="form-label fw-bold">Parts total</label>
                <div class="input-group">
                    <span class="input-group-text">৳</span>
                    <input :value="Math.round(partsTotal)" type="text" class="form-control bg-light text-end fw-bold" readonly>
                </div>
                <input type="hidden" name="compare_price" :value="Math.round(partsTotal)">
                <div class="form-text">Bought separately. Shown struck through in the shop.</div>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">Combo price *</label>
                <div class="input-group">
                    <span class="input-group-text">৳</span>
                    <input
                        v-model="finalPrice"
                        type="number"
                        min="0"
                        step="1"
                        name="price"
                        class="form-control text-end fw-bold"
                        required
                        @input="priceTouched = true"
                    >
                </div>
                <div class="form-text">
                    <template v-if="priceTouched">
                        <button type="button" class="btn btn-link btn-sm p-0 align-baseline" @click="resetPrice">
                            Reset to parts total
                        </button>
                    </template>
                    <template v-else>Follows the parts total until you change it.</template>
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">Customer saves</label>
                <div
                    class="form-control text-end fw-bold"
                    :class="savings > 0 ? 'bg-success-subtle text-success' : (savings < 0 ? 'bg-danger-subtle text-danger' : 'bg-light')"
                >
                    {{ money(savings) }}
                    <span v-if="savings > 0" class="small">({{ savingsPercent }}%)</span>
                </div>
                <div v-if="savings < 0" class="form-text text-danger">
                    The combo costs more than buying the parts.
                </div>
            </div>
        </div>

        <div v-if="buildable !== null" class="mt-3">
            <span class="badge fs-6 px-3 py-2"
                  :class="buildable > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'">
                {{ buildable }} bundle(s) can be built from current stock
            </span>
        </div>
    </div>
</template>
