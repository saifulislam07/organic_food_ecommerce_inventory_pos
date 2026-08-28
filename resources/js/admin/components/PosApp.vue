<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import http, { errorMessage } from '../../shared/http';
import { money } from '../../shared/format';

const props = defineProps({
    items: { type: Array, default: () => [] },
    searchUrl: { type: String, required: true },
    storeUrl: { type: String, required: true },
    paymentMethods: { type: Array, default: () => [] },
    defaultPaymentMethod: { type: String, default: 'cash' },
});

const cart = ref([]);
const deliveryCharge = ref(0);
const discountAmount = ref(0);
const paymentMethod = ref(props.defaultPaymentMethod);

const query = ref('');
const results = ref([]);
const searching = ref(false);
const showResults = ref(false);
const searchBox = ref(null);
let searchTimer = null;

const showCheckout = ref(false);
const submitting = ref(false);
const alert = ref(null);

const form = ref({
    customer_name: 'Walk-in Customer',
    customer_phone: '',
    customer_address: 'Shop Counter',
});

const subtotal = computed(() =>
    cart.value.reduce((sum, line) => sum + line.price * line.quantity, 0)
);

const grandTotal = computed(() =>
    Math.max(0, subtotal.value + Number(deliveryCharge.value || 0) - Number(discountAmount.value || 0))
);

const alertClass = computed(() => 'alert-' + (alert.value ? alert.value.type : 'danger'));

/** Stock still sellable for a variant: on-hand minus whatever the basket already holds. */
function stockLeft(item) {
    const line = cart.value.find((l) => l.id === item.id);

    return item.stock - (line ? line.quantity : 0);
}

function shortStockWarning(item) {
    return 'Only ' + item.stock + ' unit(s) of ' + item.product_name + ' (' + item.name + ') in stock.';
}

function notify(text, type = 'danger') {
    alert.value = { text, type };
}

function addToCart(item) {
    if (item.stock <= 0) {
        return notify(item.product_name + ' (' + item.name + ') is out of stock.');
    }

    const line = cart.value.find((l) => l.id === item.id);

    if (line) {
        if (line.quantity >= item.stock) {
            return notify(shortStockWarning(item));
        }
        line.quantity += 1;
    } else {
        cart.value.push({
            id: item.id,
            name: item.name,
            product_name: item.product_name,
            image: item.image,
            price: item.price,
            stock: item.stock,
            quantity: 1,
        });
    }

    alert.value = null;
}

function changeQty(line, delta) {
    const next = line.quantity + delta;

    if (next > line.stock) {
        return notify(shortStockWarning(line));
    }

    if (next <= 0) {
        return removeLine(line);
    }

    line.quantity = next;
    alert.value = null;
}

function setQty(line, value) {
    const next = Math.floor(Number(value));

    if (!Number.isFinite(next) || next <= 0) {
        return removeLine(line);
    }

    line.quantity = Math.min(next, line.stock);

    if (next > line.stock) {
        notify(shortStockWarning(line));
    }
}

function removeLine(line) {
    cart.value = cart.value.filter((l) => l.id !== line.id);
}

function clearCart() {
    if (cart.value.length && !window.confirm('Clear entire basket?')) {
        return;
    }

    cart.value = [];
    deliveryCharge.value = 0;
    discountAmount.value = 0;
    paymentMethod.value = props.defaultPaymentMethod;
    alert.value = null;
}

watch(query, (value) => {
    clearTimeout(searchTimer);

    if (value.trim().length < 2) {
        results.value = [];
        showResults.value = false;
        return;
    }

    searchTimer = setTimeout(runSearch, 250);
});

async function runSearch() {
    searching.value = true;
    showResults.value = true;

    try {
        const { data } = await http.get(props.searchUrl, { params: { q: query.value.trim() } });
        results.value = data;
    } catch (error) {
        results.value = [];
        notify(errorMessage(error, 'Search failed.'));
    } finally {
        searching.value = false;
    }
}

/** Enter picks the first hit, so a barcode scanner can drive the basket. */
function pickFirstResult() {
    if (results.value.length) {
        pickResult(results.value[0]);
    }
}

function pickResult(item) {
    addToCart(item);
    query.value = '';
    results.value = [];
    showResults.value = false;
}

function openCheckout() {
    if (!cart.value.length) return;

    alert.value = null;
    showCheckout.value = true;
}

async function submitOrder() {
    submitting.value = true;
    alert.value = null;

    try {
        const { data } = await http.post(props.storeUrl, {
            customer_name: form.value.customer_name,
            customer_phone: form.value.customer_phone,
            customer_address: form.value.customer_address,
            delivery_charge: Number(deliveryCharge.value || 0),
            discount_amount: Number(discountAmount.value || 0),
            payment_method: paymentMethod.value,
            items: cart.value.map((line) => ({ variant_id: line.id, quantity: line.quantity })),
        });

        if (data.redirect) {
            window.location.href = data.redirect;
            return;
        }

        submitting.value = false;
    } catch (error) {
        submitting.value = false;
        showCheckout.value = false;
        notify(errorMessage(error, 'Could not create the order.'));
    }
}

function onDocumentClick(event) {
    if (searchBox.value && !searchBox.value.contains(event.target)) {
        showResults.value = false;
    }
}

function onKeydown(event) {
    if (event.key !== 'Escape') return;

    if (showCheckout.value) {
        if (!submitting.value) showCheckout.value = false;
    } else {
        showResults.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    clearTimeout(searchTimer);
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div>
        <div v-if="alert" class="alert d-flex align-items-center gap-2" :class="alertClass">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span class="flex-grow-1">{{ alert.text }}</span>
            <button type="button" class="btn-close" @click="alert = null"></button>
        </div>

        <div class="row g-4">
            <!-- Product selection -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div ref="searchBox" class="position-relative mb-4">
                            <div class="input-group input-group-lg border rounded-pill overflow-hidden bg-light">
                                <span class="input-group-text bg-transparent border-0 ps-4">
                                    <i class="bi" :class="searching ? 'bi-hourglass-split' : 'bi-search'" style="color:#2d6a4f;"></i>
                                </span>
                                <input
                                    v-model="query"
                                    type="text"
                                    class="form-control bg-transparent border-0 shadow-none"
                                    placeholder="Search product by name or SKU…"
                                    @focus="showResults = results.length > 0"
                                    @keydown.enter.prevent="pickFirstResult"
                                >
                            </div>

                            <div v-if="showResults" class="list-group pos-search-results mt-2">
                                <div v-if="searching" class="list-group-item text-center py-3 text-muted">
                                    Searching…
                                </div>
                                <div v-else-if="!results.length" class="list-group-item text-center py-3 text-muted">
                                    No products found
                                </div>
                                <button
                                    v-for="item in results"
                                    :key="item.id"
                                    type="button"
                                    class="list-group-item list-group-item-action d-flex align-items-center gap-3 p-3"
                                    @click="pickResult(item)"
                                >
                                    <img :src="item.image" :alt="item.product_name" class="rounded" width="40" height="40" style="object-fit:cover;">
                                    <div class="text-start">
                                        <h6 class="mb-0 fw-bold small">{{ item.product_name }}</h6>
                                        <small class="text-muted" style="font-size:.7rem;">
                                            {{ item.name }} — SKU: {{ item.sku || 'N/A' }}
                                        </small>
                                    </div>
                                    <div class="ms-auto text-end">
                                        <span class="fw-bold d-block small">{{ money(item.price) }}</span>
                                        <small class="badge" :class="item.stock < 10 ? 'bg-danger' : 'bg-success'" style="font-size:.6rem;">
                                            {{ item.stock }} left
                                        </small>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <div v-if="!items.length" class="text-center py-5 text-muted">
                            <i class="bi bi-box-seam fs-1 d-block mb-2 opacity-25"></i>
                            No products available yet.
                        </div>

                        <div v-else class="row g-3">
                            <div v-for="item in items" :key="item.id" class="col-md-4 col-sm-6">
                                <button
                                    type="button"
                                    class="card h-100 w-100 pos-product-card shadow-sm p-0 text-start"
                                    :disabled="stockLeft(item) <= 0"
                                    @click="addToCart(item)"
                                >
                                    <img :src="item.image" :alt="item.product_name" class="card-img-top p-2 rounded" style="height:120px;object-fit:cover;">
                                    <div class="card-body p-2 text-center">
                                        <h6 class="mb-1 fw-bold text-truncate small">{{ item.product_name }}</h6>
                                        <small class="text-muted d-block mb-2" style="font-size:.75rem;">{{ item.name }}</small>
                                        <div class="d-flex justify-content-between align-items-center bg-light rounded px-2 py-1">
                                            <span class="fw-bold small" style="color:#2d6a4f;">{{ money(item.price) }}</span>
                                            <small
                                                class="badge bg-white border"
                                                :class="stockLeft(item) < 5 ? 'text-danger' : 'text-success'"
                                                style="font-size:.65rem;"
                                            >{{ stockLeft(item) }}</small>
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Basket -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-dark">Current Order</h5>
                        <span v-if="cart.length" class="badge bg-secondary-subtle text-secondary">{{ cart.length }} item(s)</span>
                    </div>

                    <div class="card-body pos-cart-container pt-0">
                        <div v-if="!cart.length" class="text-center py-5 text-muted">
                            <i class="bi bi-cart3 fs-1 d-block mb-2 opacity-25"></i>
                            Basket is empty
                        </div>

                        <div v-else class="list-group list-group-flush mb-4">
                            <div v-for="line in cart" :key="line.id" class="list-group-item bg-transparent border-0 px-0 mb-3">
                                <div class="d-flex gap-3">
                                    <img :src="line.image" :alt="line.product_name" class="rounded" width="50" height="50" style="object-fit:cover;">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-0 fw-bold small">{{ line.product_name }}</h6>
                                                <small class="text-muted" style="font-size:.7rem;">{{ line.name }}</small>
                                            </div>
                                            <button type="button" class="btn btn-sm text-danger p-0 border-0" @click="removeLine(line)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <div class="input-group input-group-sm" style="width:110px;">
                                                <button type="button" class="btn btn-outline-secondary border-0 bg-light" @click="changeQty(line, -1)">-</button>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    :max="line.stock"
                                                    class="form-control text-center bg-white border-0 fw-bold"
                                                    :value="line.quantity"
                                                    @change="setQty(line, $event.target.value)"
                                                >
                                                <button type="button" class="btn btn-outline-secondary border-0 bg-light" @click="changeQty(line, 1)">+</button>
                                            </div>
                                            <span class="fw-bold text-dark">{{ money(line.price * line.quantity) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-light border-0 p-4">
                        <div class="d-flex justify-content-between mb-2 small text-muted">
                            <span>Subtotal:</span>
                            <span>{{ money(subtotal) }}</span>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="small text-muted mb-1">Delivery:</label>
                                <input v-model.number="deliveryCharge" type="number" min="0" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="small text-muted mb-1">Discount (৳):</label>
                                <input v-model.number="discountAmount" type="number" min="0" class="form-control form-control-sm">
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <h5 class="fw-bold mb-0">Total:</h5>
                            <h5 class="fw-bold mb-0" style="color:#2d6a4f;">{{ money(grandTotal) }}</h5>
                        </div>

                        <button
                            type="button"
                            class="btn btn-primary w-100 py-3 fw-bold shadow-sm mb-3"
                            :disabled="!cart.length"
                            @click="openCheckout"
                        >
                            PROCESS ORDER <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger w-100 btn-sm border-0" @click="clearCart">
                            Clear Basket
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Checkout modal (hand rolled so it does not depend on Bootstrap's JS) -->
        <Teleport to="body">
            <div v-if="showCheckout">
                <div class="modal fade show d-block" tabindex="-1" @click.self="submitting || (showCheckout = false)">
                    <div class="modal-dialog">
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-header bg-primary text-white border-0">
                                <h5 class="modal-title fw-bold">Customer Details</h5>
                                <button type="button" class="btn-close btn-close-white" :disabled="submitting" @click="showCheckout = false"></button>
                            </div>
                            <div class="modal-body p-4">
                                <form @submit.prevent="submitOrder">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Customer Name</label>
                                        <input v-model="form.customer_name" type="text" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Phone Number</label>
                                        <input v-model="form.customer_phone" type="text" class="form-control" required placeholder="01XXXXXXXXX">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Delivery Address</label>
                                        <textarea v-model="form.customer_address" class="form-control" rows="3" required></textarea>
                                    </div>

                                    <div class="mb-3" v-if="paymentMethods.length">
                                        <label class="form-label small fw-bold text-muted">Paid by</label>
                                        <select v-model="paymentMethod" class="form-select" required>
                                            <option v-for="method in paymentMethods" :key="method.value" :value="method.value">
                                                {{ method.label }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="d-flex justify-content-between mb-3 fw-bold">
                                        <span>Payable:</span>
                                        <span style="color:#2d6a4f;">{{ money(grandTotal) }}</span>
                                    </div>

                                    <button type="submit" class="btn btn-success w-100 py-3 fw-bold" :disabled="submitting">
                                        <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                                        {{ submitting ? 'Processing…' : 'CONFIRM & PRINT' }}
                                        <i v-if="!submitting" class="bi bi-printer ms-2"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-backdrop fade show"></div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.pos-product-card {
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid transparent;
    background: #fff;
}

.pos-product-card:hover:not(:disabled) {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important;
    border-color: #2d6a4f;
}

.pos-product-card:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.pos-cart-container {
    height: calc(100vh - 250px);
    overflow-y: auto;
}

.pos-search-results {
    position: absolute;
    width: 100%;
    z-index: 1050;
    max-height: 400px;
    overflow-y: auto;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}
</style>
