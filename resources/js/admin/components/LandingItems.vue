<script setup>
import { computed, ref, watch } from 'vue';
import VariantSelect from './VariantSelect.vue';
import { money } from '../../shared/format';

/**
 * What a landing page sells, and how it is offered.
 *
 * The mode picker lives in here rather than in the Blade around it because
 * everything below it changes shape with the answer: a single-package page
 * needs a default selection, a bundle needs one price for the lot.
 *
 * Every field posts with the surrounding form under items[i][…].
 */
const props = defineProps({
    rows: { type: Array, default: () => [] },
    variants: { type: Array, default: () => [] },
    modes: { type: Object, default: () => ({}) },
    mode: { type: String, default: 'single' },
    bundlePrice: { type: [Number, String, null], default: null },
    errors: { type: Object, default: () => ({}) },
});

function blankRow() {
    return {
        id: null,
        product_variant_id: null,
        label: '',
        offer_price: '',
        compare_at_price: '',
        min_qty: 1,
        max_qty: 10,
        image_url: null,
    };
}

const mode = ref(props.mode);
const bundlePrice = ref(props.bundlePrice ?? '');

const rows = ref(
    props.rows.length
        ? props.rows.map((row) => ({
              id: row.id ?? null,
              product_variant_id: row.product_variant_id ?? null,
              label: row.label ?? '',
              offer_price: row.offer_price ?? '',
              compare_at_price: row.compare_at_price ?? '',
              min_qty: Number(row.min_qty ?? 1),
              max_qty: Number(row.max_qty ?? 10),
              image_url: row.image_url ?? null,
          }))
        : [blankRow()]
);

/** Which package opens pre-selected. Only meaningful for single-choice pages. */
const defaultIndex = ref(Math.max(0, props.rows.findIndex((row) => row.is_default)));

function variantFor(id) {
    return props.variants.find((variant) => String(variant.id) === String(id)) || null;
}

/** The offer price if one was typed, otherwise the shop's own price. */
function unitPrice(row) {
    if (row.offer_price !== '' && row.offer_price !== null) return Number(row.offer_price) || 0;

    return Number(variantFor(row.product_variant_id)?.price ?? 0);
}

function lineTotal(row) {
    return unitPrice(row) * (Number(row.min_qty) || 1);
}

const partsTotal = computed(() => rows.value.reduce((sum, row) => sum + lineTotal(row), 0));

const bundleSavings = computed(() => partsTotal.value - (Number(bundlePrice.value) || 0));

/** A product already on the page is taken out of every other row's list. */
function optionsFor(index) {
    const taken = rows.value
        .filter((_, i) => i !== index)
        .map((row) => String(row.product_variant_id))
        .filter((id) => id !== 'null' && id !== '');

    return props.variants.filter((variant) => !taken.includes(String(variant.id)));
}

function addRow() {
    rows.value.push(blankRow());
}

function removeRow(index) {
    rows.value.splice(index, 1);

    if (!rows.value.length) addRow();
    if (defaultIndex.value >= rows.value.length) defaultIndex.value = 0;
}

function move(index, direction) {
    const target = index + direction;

    if (target < 0 || target >= rows.value.length) return;

    const [row] = rows.value.splice(index, 1);
    rows.value.splice(target, 0, row);

    if (defaultIndex.value === index) defaultIndex.value = target;
    else if (defaultIndex.value === target) defaultIndex.value = index;
}

// Leaving bundle mode makes the bundle price meaningless; clearing it stops a
// stale number being saved and quietly discounting a later campaign.
watch(mode, (value) => {
    if (value !== 'bundle') bundlePrice.value = '';
});

function errorFor(index, field) {
    return props.errors[`items.${index}.${field}`]?.[0] ?? null;
}
</script>

<template>
    <div>
        <div class="mb-4">
            <label class="form-label fw-bold">এই পেজে কীভাবে বিক্রি হবে *</label>
            <select v-model="mode" name="selection_mode" class="form-select">
                <option v-for="(label, key) in modes" :key="key" :value="key">{{ label }}</option>
            </select>
            <div class="form-text">
                <template v-if="mode === 'single'">ক্রেতা একটি প্যাকেজ বেছে নেবে — যেমন ১ কেজি / ৩ কেজি / ৫ কেজি।</template>
                <template v-else-if="mode === 'multi'">ক্রেতা একাধিক আইটেম নিতে পারবে, প্রতিটির পরিমাণ আলাদা।</template>
                <template v-else>সব আইটেম একসাথে একটাই কম্বো, একটাই দাম।</template>
            </div>
        </div>

        <div v-if="errors.items" class="alert alert-danger py-2 small">
            <i class="bi bi-exclamation-triangle-fill"></i> {{ errors.items[0] }}
        </div>

        <div v-for="(row, index) in rows" :key="index" class="card bg-light border-0 p-3 mb-3">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge bg-secondary">{{ index + 1 }}</span>

                <label v-if="mode === 'single'" class="form-check-label d-flex align-items-center gap-1 small">
                    <input type="radio" class="form-check-input mt-0" :checked="defaultIndex === index"
                           @change="defaultIndex = index">
                    আগে থেকে সিলেক্টেড
                </label>

                <div class="btn-group btn-group-sm ms-auto">
                    <button type="button" class="btn btn-outline-secondary" :disabled="index === 0"
                            title="উপরে" @click="move(index, -1)">
                        <i class="bi bi-arrow-up"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary" :disabled="index === rows.length - 1"
                            title="নিচে" @click="move(index, 1)">
                        <i class="bi bi-arrow-down"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger" title="সরান" @click="removeRow(index)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>

            <input type="hidden" :name="`items[${index}][id]`" :value="row.id ?? ''">
            <input type="hidden" :name="`items[${index}][is_default]`"
                   :value="(mode === 'single' ? defaultIndex === index : index === 0) ? 1 : 0">

            <div class="row g-3">
                <div class="col-lg-6">
                    <label class="form-label small text-muted">প্রোডাক্ট *</label>
                    <VariantSelect
                        v-model="row.product_variant_id"
                        :variants="optionsFor(index)"
                        :name="`items[${index}][product_variant_id]`"
                        :invalid="!!errorFor(index, 'product_variant_id')"
                        placeholder="প্রোডাক্ট খুঁজুন…"
                    />
                    <div v-if="errorFor(index, 'product_variant_id')" class="text-danger small mt-1">
                        {{ errorFor(index, 'product_variant_id') }}
                    </div>
                </div>

                <div class="col-lg-6">
                    <label class="form-label small text-muted">পেজে যে নামে দেখাবে</label>
                    <input v-model="row.label" type="text" :name="`items[${index}][label]`"
                           class="form-control" placeholder="খালি রাখলে প্রোডাক্টের নাম বসবে">
                </div>

                <div class="col-6 col-lg-3">
                    <label class="form-label small text-muted">অফার দাম</label>
                    <div class="input-group">
                        <span class="input-group-text">৳</span>
                        <input v-model="row.offer_price" type="number" min="0" step="1"
                               :name="`items[${index}][offer_price]`" class="form-control text-end"
                               :placeholder="String(Math.round(Number(variantFor(row.product_variant_id)?.price ?? 0)))">
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <label class="form-label small text-muted">কাটা দাম</label>
                    <div class="input-group">
                        <span class="input-group-text">৳</span>
                        <input v-model="row.compare_at_price" type="number" min="0" step="1"
                               :name="`items[${index}][compare_at_price]`" class="form-control text-end">
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label small text-muted">কমপক্ষে</label>
                    <input v-model.number="row.min_qty" type="number" min="1" max="100"
                           :name="`items[${index}][min_qty]`" class="form-control text-end">
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label small text-muted">সর্বোচ্চ</label>
                    <input v-model.number="row.max_qty" type="number" min="1" max="100"
                           :name="`items[${index}][max_qty]`" class="form-control text-end">
                </div>

                <div class="col-lg-2">
                    <label class="form-label small text-muted">লাইন টোটাল</label>
                    <div class="form-control bg-white text-end fw-bold">{{ money(lineTotal(row)) }}</div>
                </div>

                <div class="col-12">
                    <label class="form-label small text-muted">
                        আলাদা ছবি
                        <span v-if="row.image_url" class="text-success">— একটি আছে</span>
                    </label>
                    <div class="d-flex align-items-center gap-2">
                        <img v-if="row.image_url" :src="row.image_url" alt="" class="rounded border"
                             style="width:48px;height:48px;object-fit:cover;">
                        <input type="file" :name="`item_images[${index}]`" accept="image/*" class="form-control">
                    </div>
                    <div class="form-text">খালি রাখলে প্রোডাক্টের নিজের ছবি ব্যবহার হবে।</div>
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-sm btn-outline-success" @click="addRow">
            <i class="bi bi-plus-circle"></i> আরেকটি প্রোডাক্ট
        </button>

        <div class="row g-3 mt-3 pt-3 border-top">
            <div class="col-md-4">
                <label class="form-label fw-bold">আলাদা আলাদা দামের যোগফল</label>
                <div class="form-control bg-light text-end fw-bold">{{ money(partsTotal) }}</div>
            </div>

            <template v-if="mode === 'bundle'">
                <div class="col-md-4">
                    <label class="form-label fw-bold">কম্বো দাম *</label>
                    <div class="input-group">
                        <span class="input-group-text">৳</span>
                        <input v-model="bundlePrice" type="number" min="0" step="1" name="bundle_price"
                               class="form-control text-end fw-bold"
                               :class="{ 'is-invalid': errors.bundle_price }">
                    </div>
                    <div v-if="errors.bundle_price" class="text-danger small mt-1">{{ errors.bundle_price[0] }}</div>
                    <div v-else class="form-text">খালি রাখলে যোগফলটাই দাম হবে।</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">ক্রেতার সাশ্রয়</label>
                    <div class="form-control text-end fw-bold"
                         :class="bundleSavings > 0 ? 'bg-success-subtle text-success' : 'bg-light'">
                        {{ money(bundleSavings) }}
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
