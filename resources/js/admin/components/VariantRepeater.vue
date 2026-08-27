<script setup>
import { ref } from 'vue';

/**
 * Repeating variant rows inside the product form. These are ordinary named
 * inputs (variants[i][field]) so the existing multipart POST keeps working.
 */
const props = defineProps({
    rows: { type: Array, default: () => [] },
    units: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
});

function blankRow() {
    return { name: '', unit_id: '', unit_value: '', price: '', sale_price: '', stock: 0 };
}

const variants = ref(
    props.rows.length
        ? props.rows.map((row) => ({
              name: row.name ?? '',
              unit_id: row.unit_id ?? '',
              unit_value: row.unit_value ?? '',
              price: row.price ?? '',
              sale_price: row.sale_price ?? '',
              stock: row.stock ?? 0,
          }))
        : [blankRow()]
);

function addRow() {
    variants.value.push(blankRow());
}

function removeRow(index) {
    if (variants.value.length === 1) {
        variants.value = [blankRow()];
        return;
    }

    variants.value.splice(index, 1);
}

function duplicateRow(index) {
    variants.value.splice(index + 1, 0, { ...variants.value[index], name: '' });
}

function error(index, field) {
    const messages = props.errors[`variants.${index}.${field}`];

    return Array.isArray(messages) && messages.length ? messages[0] : null;
}

/** Sale price above the regular price is almost always a typo. */
function saleAbovePrice(row) {
    return row.sale_price !== '' && row.price !== '' && Number(row.sale_price) > Number(row.price);
}
</script>

<template>
    <div>
        <div v-for="(row, index) in variants" :key="index" class="variant-row border rounded p-3 mb-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Variant Name *</label>
                    <input
                        v-model="row.name"
                        type="text"
                        :name="`variants[${index}][name]`"
                        class="form-control form-control-sm"
                        :class="{ 'is-invalid': error(index, 'name') }"
                        placeholder="e.g. ৬ কেজি"
                        required
                    >
                    <div v-if="error(index, 'name')" class="invalid-feedback d-block">{{ error(index, 'name') }}</div>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Qty</label>
                    <input
                        v-model="row.unit_value"
                        type="number"
                        step="0.001"
                        min="0"
                        :name="`variants[${index}][unit_value]`"
                        class="form-control form-control-sm"
                        placeholder="3"
                    >
                </div>
                <div class="col-md-1">
                    <label class="form-label">Unit</label>
                    <select
                        v-model="row.unit_id"
                        :name="`variants[${index}][unit_id]`"
                        class="form-select form-select-sm"
                    >
                        <option value="">—</option>
                        <option v-for="unit in units" :key="unit.id" :value="unit.id">
                            {{ unit.short_code }}
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Price *</label>
                    <input
                        v-model="row.price"
                        type="number"
                        min="0"
                        :name="`variants[${index}][price]`"
                        class="form-control form-control-sm"
                        :class="{ 'is-invalid': error(index, 'price') }"
                        required
                    >
                    <div v-if="error(index, 'price')" class="invalid-feedback d-block">{{ error(index, 'price') }}</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sale Price</label>
                    <input
                        v-model="row.sale_price"
                        type="number"
                        min="0"
                        :name="`variants[${index}][sale_price]`"
                        class="form-control form-control-sm"
                        :class="{ 'is-invalid': saleAbovePrice(row) || error(index, 'sale_price') }"
                    >
                </div>
                <div class="col-md-2">
                    <label class="form-label">Stock *</label>
                    <input
                        v-model="row.stock"
                        type="number"
                        min="0"
                        :name="`variants[${index}][stock]`"
                        class="form-control form-control-sm"
                        :class="{ 'is-invalid': error(index, 'stock') }"
                        required
                    >
                    <div v-if="error(index, 'stock')" class="invalid-feedback d-block">{{ error(index, 'stock') }}</div>
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Duplicate" @click="duplicateRow(index)">
                        <i class="bi bi-files"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" title="Remove" @click="removeRow(index)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>

            <div v-if="saleAbovePrice(row)" class="small text-danger mt-2">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                Sale price is higher than the regular price.
            </div>
        </div>

        <button type="button" class="btn btn-sm btn-outline-success mt-2" @click="addRow">
            <i class="bi bi-plus-circle"></i> Add Variant
        </button>
    </div>
</template>
