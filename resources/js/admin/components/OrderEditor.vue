<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import http from '../../shared/http';
import { money } from '../../shared/format';

/**
 * The money half of the order edit form: the lines, the delivery charge and the
 * discount, with a running total.
 *
 * These are ordinary named inputs (items[i][field]) inside the page's normal
 * PUT form, the same way VariantRepeater works — so validation errors, old()
 * repopulation and the back button all behave as they do everywhere else in the
 * panel. The component owns delivery and discount as well as the lines, because
 * a total that updates as you type is the whole point and it cannot be worked
 * out without them.
 */
const props = defineProps({
    lines: { type: Array, default: () => [] },
    searchUrl: { type: String, required: true },
    deliveryCharge: { type: [Number, String], default: 0 },
    discountAmount: { type: [Number, String], default: 0 },
    discountType: { type: String, default: 'flat' },
    errors: { type: Object, default: () => ({}) },
});

const items = ref(
    props.lines.map((line) => ({
        variant_id: line.variant_id,
        product_name: line.product_name,
        variant_name: line.variant_name,
        quantity: Number(line.quantity) || 1,
        unit_price: Number(line.unit_price) || 0,
        // What could be sold if this line went back on the shelf. Null means
        // the variant is gone, so there is no ceiling to enforce.
        stock: line.stock === null || line.stock === undefined ? null : Number(line.stock),
    }))
);

const delivery = ref(Number(props.deliveryCharge) || 0);
const discount = ref(Number(props.discountAmount) || 0);
const discountType = ref(props.discountType || 'flat');

/* --------------------------------------------------------------- totals */

const subtotal = computed(() =>
    items.value.reduce((sum, item) => sum + (Number(item.unit_price) || 0) * (Number(item.quantity) || 0), 0)
);

/** Mirrors the server's discountFor(): percent is of the subtotal, and neither form may exceed it. */
const discountValue = computed(() => {
    const raw = Number(discount.value) || 0;
    const worth = discountType.value === 'percent' ? subtotal.value * (Math.min(raw, 100) / 100) : raw;

    return Math.min(Math.max(worth, 0), subtotal.value);
});

const total = computed(() => Math.max(0, subtotal.value + (Number(delivery.value) || 0) - discountValue.value));

/* ---------------------------------------------------------------- lines */

function lineTotal(item) {
    return (Number(item.unit_price) || 0) * (Number(item.quantity) || 0);
}

/** True when this line is asking for more than there is. */
function overStock(item) {
    return item.stock !== null && Number(item.quantity) > item.stock;
}

const shortOfStock = computed(() => items.value.some(overStock));

function removeLine(index) {
    items.value.splice(index, 1);
}

function addVariant(variant) {
    const existing = items.value.find((item) => item.variant_id === variant.id);

    // Adding something twice raises the quantity rather than opening a second
    // line for it — the server merges duplicates anyway, and two lines for one
    // product is confusing to look at before it ever gets there.
    if (existing) {
        existing.quantity += 1;
    } else {
        items.value.push({
            variant_id: variant.id,
            product_name: variant.product_name,
            variant_name: variant.name,
            quantity: 1,
            unit_price: variant.price,
            stock: variant.stock,
        });
    }

    closeSearch();
}

/* ------------------------------------------------------------- searching */

const query = ref('');
const results = ref([]);
const searching = ref(false);
const open = ref(false);
const searchBox = ref(null);
let timer = null;

function onType() {
    clearTimeout(timer);

    if (query.value.trim().length < 2) {
        results.value = [];
        open.value = false;
        return;
    }

    timer = setTimeout(runSearch, 250);
}

async function runSearch() {
    searching.value = true;

    try {
        const { data } = await http.get(props.searchUrl, { params: { q: query.value.trim() } });
        results.value = Array.isArray(data) ? data : [];
        open.value = true;
    } catch {
        results.value = [];
    } finally {
        searching.value = false;
    }
}

function closeSearch() {
    query.value = '';
    results.value = [];
    open.value = false;
}

function onClickOutside(event) {
    if (searchBox.value && !searchBox.value.contains(event.target)) {
        open.value = false;
    }
}

onMounted(() => document.addEventListener('click', onClickOutside));
onBeforeUnmount(() => {
    document.removeEventListener('click', onClickOutside);
    clearTimeout(timer);
});

function error(key) {
    const messages = props.errors[key];

    return Array.isArray(messages) && messages.length ? messages[0] : null;
}

const searchInput = ref(null);

function focusSearch() {
    nextTick(() => searchInput.value?.focus());
}
</script>

<template>
    <div>
        <!-- Add a product -->
        <div ref="searchBox" class="position-relative mb-3">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="bi" :class="searching ? 'bi-hourglass-split' : 'bi-search'"></i>
                </span>
                <input
                    ref="searchInput"
                    v-model="query"
                    type="text"
                    class="form-control"
                    placeholder="Add a product — type a name or SKU"
                    autocomplete="off"
                    @input="onType"
                    @focus="results.length && (open = true)"
                >
            </div>

            <div
                v-if="open"
                class="list-group position-absolute w-100 shadow"
                style="z-index: 20; max-height: 320px; overflow-y: auto;"
            >
                <button
                    v-for="variant in results"
                    :key="variant.id"
                    type="button"
                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                    @click="addVariant(variant)"
                >
                    <span>
                        <strong>{{ variant.product_name }}</strong>
                        <small class="text-muted ms-1">{{ variant.name }}</small>
                        <small v-if="variant.sku" class="text-muted ms-1">· {{ variant.sku }}</small>
                    </span>
                    <span class="text-nowrap">
                        <span class="fw-bold">{{ money(variant.price) }}</span>
                        <small
                            class="ms-2"
                            :class="variant.stock > 0 ? 'text-muted' : 'text-danger'"
                        >{{ variant.stock }} left</small>
                    </span>
                </button>
                <div v-if="!results.length && !searching" class="list-group-item text-muted small">
                    Nothing matched “{{ query }}”.
                </div>
            </div>
        </div>

        <!-- Lines -->
        <div v-if="error('items')" class="alert alert-danger py-2 small">{{ error('items') }}</div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th style="width: 140px;">Unit price</th>
                        <th style="width: 120px;">Qty</th>
                        <th class="text-end" style="width: 120px;">Total</th>
                        <th style="width: 48px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in items" :key="`${item.variant_id}-${index}`">
                        <td>
                            <input type="hidden" :name="`items[${index}][variant_id]`" :value="item.variant_id">
                            <!--
                                Names are posted but never validated, so the server
                                ignores them; they exist so a rejected save comes
                                back showing product names rather than bare ids.
                            -->
                            <input type="hidden" :name="`items[${index}][product_name]`" :value="item.product_name">
                            <input type="hidden" :name="`items[${index}][variant_name]`" :value="item.variant_name">
                            <strong class="text-dark">{{ item.product_name }}</strong>
                            <div class="small text-muted">
                                {{ item.variant_name }}
                                <span v-if="item.stock !== null"> · {{ item.stock }} available</span>
                                <span v-else class="text-danger"> · variant deleted</span>
                            </div>
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">৳</span>
                                <input
                                    v-model.number="item.unit_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    :class="{ 'is-invalid': error(`items.${index}.unit_price`) }"
                                    :name="`items[${index}][unit_price]`"
                                >
                            </div>
                        </td>
                        <td>
                            <input
                                v-model.number="item.quantity"
                                type="number"
                                min="1"
                                class="form-control form-control-sm"
                                :class="{ 'is-invalid': overStock(item) || error(`items.${index}.quantity`) }"
                                :name="`items[${index}][quantity]`"
                            >
                        </td>
                        <td class="text-end fw-bold">{{ money(lineTotal(item)) }}</td>
                        <td class="text-end">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                title="Remove this line"
                                @click="removeLine(index)"
                            >
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!items.length">
                        <td colspan="5" class="text-center text-muted py-4">
                            This order has no items.
                            <button type="button" class="btn btn-link p-0" @click="focusSearch">Add one</button>.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="shortOfStock" class="alert alert-warning py-2 small">
            <i class="bi bi-exclamation-triangle"></i>
            One or more lines ask for more than is in stock. Saving will be refused — the figure shown already
            includes what this order is currently holding.
        </div>

        <!-- Money -->
        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Delivery charge</label>
                <div class="input-group">
                    <span class="input-group-text">৳</span>
                    <input
                        v-model.number="delivery"
                        type="number"
                        step="0.01"
                        min="0"
                        name="delivery_charge"
                        class="form-control"
                        :class="{ 'is-invalid': error('delivery_charge') }"
                    >
                </div>
                <div v-if="error('delivery_charge')" class="text-danger small">{{ error('delivery_charge') }}</div>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Discount</label>
                <div class="input-group">
                    <input
                        v-model.number="discount"
                        type="number"
                        step="0.01"
                        min="0"
                        name="discount_amount"
                        class="form-control"
                        :class="{ 'is-invalid': error('discount_amount') }"
                    >
                    <select v-model="discountType" name="discount_type" class="form-select" style="max-width: 90px;">
                        <option value="flat">৳</option>
                        <option value="percent">%</option>
                    </select>
                </div>
                <div v-if="discountType === 'percent'" class="form-text">
                    Worth {{ money(discountValue) }}.
                </div>
                <div v-if="error('discount_amount')" class="text-danger small">{{ error('discount_amount') }}</div>
            </div>

            <div class="col-md-4">
                <div class="border rounded p-3 bg-light">
                    <div class="d-flex justify-content-between small">
                        <span class="text-muted">Subtotal</span>
                        <span>{{ money(subtotal) }}</span>
                    </div>
                    <div v-if="discountValue > 0" class="d-flex justify-content-between small text-danger">
                        <span>Discount</span>
                        <span>−{{ money(discountValue) }}</span>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span class="text-muted">Delivery</span>
                        <span>{{ money(delivery) }}</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total</span>
                        <span>{{ money(total) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
