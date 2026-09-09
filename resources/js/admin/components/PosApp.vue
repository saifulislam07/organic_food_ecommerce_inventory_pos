<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import http, { errorMessage } from '../../shared/http';
import { money } from '../../shared/format';

const props = defineProps({
    items: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    searchUrl: { type: String, required: true },
    customerUrl: { type: String, required: true },
    storeUrl: { type: String, required: true },
    paymentMethods: { type: Array, default: () => [] },
    defaultPaymentMethod: { type: String, default: 'cash' },
    sources: { type: Array, default: () => [] },
});

const cart = ref([]);
const deliveryCharge = ref(0);
const discountValue = ref(0);
/** 'flat' reads discountValue as taka, 'percent' as a share of the subtotal. */
const discountType = ref('flat');
const paymentMethod = ref(props.defaultPaymentMethod);
const source = ref(props.sources[0]?.value ?? 'pos');
const notes = ref('');
const paidAmount = ref(null);

const query = ref('');
const results = ref([]);
const searching = ref(false);
const showResults = ref(false);
const searchBox = ref(null);
const searchInput = ref(null);
/** Which search hit the arrow keys are sitting on. */
const highlighted = ref(0);
let searchTimer = null;

const activeCategory = ref(null);
const categoryOpen = ref(false);
const categoryQuery = ref('');
const categoryBox = ref(null);
const categoryInput = ref(null);
const categoryHighlight = ref(0);

const showCheckout = ref(false);
const submitting = ref(false);
const alert = ref(null);
let alertTimer = null;

const form = ref({
    customer_name: 'Walk-in Customer',
    customer_phone: '',
    customer_address: 'Shop Counter',
});

/** The registered customer this sale is attached to, if any. */
const customer = ref(null);
const customerQuery = ref('');
const customerResults = ref([]);
const customerSearching = ref(false);
const customerOpen = ref(false);
const customerBox = ref(null);
const customerInput = ref(null);
let customerTimer = null;

/* ------------------------------------------------------------- the board */

const visibleItems = computed(() => {
    if (activeCategory.value === null) return props.items;

    return props.items.filter((item) => item.category_id === activeCategory.value);
});

/* ------------------------------------------------------- the category menu */

/** "Everything" is an option in the same list, so one loop renders them all. */
const categoryOptions = computed(() => [
    { id: null, name: 'All categories', count: props.items.length },
    ...props.categories,
]);

const filteredCategories = computed(() => {
    const needle = categoryQuery.value.trim().toLowerCase();

    if (!needle) return categoryOptions.value;

    return categoryOptions.value.filter((option) => option.name.toLowerCase().includes(needle));
});

const activeCategoryLabel = computed(
    () => categoryOptions.value.find((option) => option.id === activeCategory.value)?.name ?? 'All categories'
);

function toggleCategories() {
    categoryOpen.value = !categoryOpen.value;

    if (!categoryOpen.value) return;

    categoryQuery.value = '';
    categoryHighlight.value = 0;
    nextTick(() => categoryInput.value?.focus());
}

function pickCategory(option) {
    activeCategory.value = option.id;
    categoryOpen.value = false;
    categoryQuery.value = '';
    focusSearch();
}

function moveCategoryHighlight(delta) {
    const last = filteredCategories.value.length - 1;

    if (last < 0) return;

    categoryHighlight.value = Math.max(0, Math.min(last, categoryHighlight.value + delta));
}

function pickHighlightedCategory() {
    const option = filteredCategories.value[categoryHighlight.value];

    if (option) pickCategory(option);
}

/** Typing narrows the list, so the highlight cannot sit past the end of it. */
watch(categoryQuery, () => {
    categoryHighlight.value = 0;
});

/**
 * How many tiles the board shows before the Load more button.
 *
 * Measured rather than fixed: a page size picked as a number is wrong on every
 * screen but one, and being wrong high is exactly the long scroll this replaces.
 * The grid is asked how many columns it settled on and how tall a tile came out,
 * and the pane is filled to the row that still fits.
 */
const boardBody = ref(null);
const gridEl = ref(null);
const moreEl = ref(null);
/** Until the first measurement, small enough not to overflow anything. */
const pageSize = ref(12);
const shown = ref(12);

const pagedItems = computed(() => visibleItems.value.slice(0, shown.value));

const remaining = computed(() => Math.max(0, visibleItems.value.length - shown.value));

/** How many the next press actually adds, so the button can say so. */
const nextBatch = computed(() => Math.min(remaining.value, pageSize.value));

function loadMore() {
    shown.value += pageSize.value;
}

function measurePage() {
    // Below the desktop breakpoint the panes stack and the page scrolls on its
    // own, so there is no pane height to fill — a sensible batch will do.
    if (window.innerWidth < 992) {
        pageSize.value = 12;
        if (shown.value < 12) shown.value = 12;

        return;
    }

    const body = boardBody.value;
    const grid = gridEl.value;
    const tile = grid?.firstElementChild;

    if (!body || !grid || !tile) return;

    const gridStyle = window.getComputedStyle(grid);
    const columns = gridStyle.gridTemplateColumns.split(' ').filter(Boolean).length;
    const gap = parseFloat(gridStyle.rowGap) || 0;
    const rowHeight = tile.offsetHeight + gap;

    if (!columns || rowHeight <= gap) return;

    const bodyStyle = window.getComputedStyle(body);
    const padding = parseFloat(bodyStyle.paddingTop) + parseFloat(bodyStyle.paddingBottom);
    const available = body.clientHeight - padding - (moreEl.value?.offsetHeight ?? 0);

    const rows = Math.max(1, Math.floor((available + gap) / rowHeight));
    const next = rows * columns;

    if (next === pageSize.value) return;

    pageSize.value = next;
    // Already-loaded tiles stay loaded; only a first fill grows to the new size.
    if (shown.value < next) shown.value = next;
}

/** A different category is a different list, so it starts from the top again. */
watch(activeCategory, () => {
    shown.value = pageSize.value;
    nextTick(measurePage);
});

let resizeObserver = null;

/* ------------------------------------------------------------ the ticket */

const subtotal = computed(() =>
    cart.value.reduce((sum, line) => sum + line.price * line.quantity, 0)
);

const itemCount = computed(() =>
    cart.value.reduce((sum, line) => sum + line.quantity, 0)
);

/** What the offers already on these products come to across the ticket. */
const offerSaving = computed(() =>
    cart.value.reduce(
        (sum, line) => sum + Math.max(0, line.price - (line.offerPrice ?? line.price)) * line.quantity,
        0
    )
);

/**
 * Keep the discount box in step with the offers, until the cashier takes it
 * over. Typing in the box — or switching it to a percentage — hands control
 * across, and it stays theirs until the ticket is cleared.
 */
const discountTouched = ref(false);

watch(offerSaving, (saving) => {
    if (discountTouched.value || discountType.value !== 'flat') return;

    discountValue.value = saving;
}, { immediate: true });

/** A percentage cannot mirror a taka figure, so switching hands control over. */
function switchDiscountType() {
    discountType.value = discountType.value === 'flat' ? 'percent' : 'flat';
    discountTouched.value = true;
}

function resetDiscount() {
    discountTouched.value = false;
    discountType.value = 'flat';
    discountValue.value = offerSaving.value;
}

/** The discount in money, however the cashier typed it. Mirrors the server. */
const discountAmount = computed(() => {
    const value = Number(discountValue.value || 0);

    if (value <= 0) return 0;

    const money = discountType.value === 'percent'
        ? subtotal.value * (Math.min(value, 100) / 100)
        : value;

    return Math.round(Math.min(money, subtotal.value) * 100) / 100;
});

const grandTotal = computed(() =>
    Math.max(0, subtotal.value + Number(deliveryCharge.value || 0) - discountAmount.value)
);

/** What to hand back. Null until a payment figure is typed. */
const changeDue = computed(() => {
    if (paidAmount.value === null || paidAmount.value === '') return null;

    return Math.round((Number(paidAmount.value) - grandTotal.value) * 100) / 100;
});

const alertClass = computed(() => 'pos-alert-' + (alert.value ? alert.value.type : 'danger'));

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

    // A counter is busy; a warning that stays put gets read as part of the
    // furniture, so it clears itself.
    clearTimeout(alertTimer);
    alertTimer = setTimeout(() => (alert.value = null), 5000);
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
            // The line is rung up at the shelf price; anything the offer takes
            // off is collected into the discount box instead of disappearing
            // into the line, so the cashier can see and adjust it.
            price: item.list_price ?? item.price,
            offerPrice: item.price,
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
    if (cart.value.length && !window.confirm('Clear the whole order?')) {
        return;
    }

    cart.value = [];
    deliveryCharge.value = 0;
    discountValue.value = 0;
    discountType.value = 'flat';
    // The next ticket starts following the offers again.
    discountTouched.value = false;
    paymentMethod.value = props.defaultPaymentMethod;
    source.value = props.sources[0]?.value ?? 'pos';
    notes.value = '';
    paidAmount.value = null;
    detachCustomer();
    alert.value = null;
    focusSearch();
}

/* ------------------------------------------------------------- searching */

watch(query, (value) => {
    clearTimeout(searchTimer);
    highlighted.value = 0;

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
        highlighted.value = 0;
    } catch (error) {
        results.value = [];
        notify(errorMessage(error, 'Search failed.'));
    } finally {
        searching.value = false;
    }
}

/** Enter takes the highlighted hit, so a barcode scanner can drive the ticket. */
function pickHighlighted() {
    const item = results.value[highlighted.value];

    if (item) pickResult(item);
}

function moveHighlight(delta) {
    if (!results.value.length) return;

    const next = highlighted.value + delta;
    highlighted.value = Math.max(0, Math.min(results.value.length - 1, next));
}

function pickResult(item) {
    addToCart(item);
    query.value = '';
    results.value = [];
    showResults.value = false;
}

function focusSearch() {
    nextTick(() => searchInput.value?.focus());
}

/* ------------------------------------------------------------- customers */

watch(customerQuery, (value) => {
    clearTimeout(customerTimer);

    if (value.trim().length < 2) {
        customerResults.value = [];
        return;
    }

    customerTimer = setTimeout(runCustomerSearch, 300);
});

/** One lookup, shared by the panel picker and the payment form's typeahead. */
async function lookupCustomers(term) {
    try {
        const { data } = await http.get(props.customerUrl, { params: { q: term } });

        return data;
    } catch (error) {
        notify(errorMessage(error, 'Customer lookup failed.'));

        return [];
    }
}

async function runCustomerSearch() {
    customerSearching.value = true;
    customerResults.value = await lookupCustomers(customerQuery.value.trim());
    customerSearching.value = false;
}

/* --------------------------------------------- typeahead on the pay form */

/**
 * Which of the two payment-form fields is showing suggestions, if either.
 *
 * A cashier who went straight to Charge without touching the panel picker can
 * still attach a regular by typing the name or number they already know.
 */
const suggestFor = ref(null);
const suggestResults = ref([]);
const suggestSearching = ref(false);
let suggestTimer = null;

function onFormInput(field) {
    // Someone is already attached; correcting their name here is an edit to
    // this one sale, not a search for somebody else.
    if (customer.value) return;

    const term = (field === 'name' ? form.value.customer_name : form.value.customer_phone).trim();

    clearTimeout(suggestTimer);

    if (term.length < 2) {
        suggestFor.value = null;
        suggestResults.value = [];

        return;
    }

    suggestFor.value = field;
    suggestSearching.value = true;

    suggestTimer = setTimeout(async () => {
        suggestResults.value = await lookupCustomers(term);
        suggestSearching.value = false;
    }, 300);
}

/** Moving to the other field drops the list the first one was showing. */
function onFormFocus(field) {
    if (suggestFor.value !== field) closeSuggestions();
}

function closeSuggestions() {
    clearTimeout(suggestTimer);
    suggestFor.value = null;
    suggestResults.value = [];
    suggestSearching.value = false;
}

function toggleCustomerFinder() {
    customerOpen.value = !customerOpen.value;

    if (!customerOpen.value) return;

    customerQuery.value = '';
    customerResults.value = [];
    nextTick(() => customerInput.value?.focus());
}

function pickCustomer(picked) {
    customer.value = picked;
    form.value.customer_name = picked.name;
    form.value.customer_phone = picked.phone ?? '';

    if (picked.address) form.value.customer_address = picked.address;

    customerQuery.value = '';
    customerResults.value = [];
    customerOpen.value = false;
    closeSuggestions();
    focusSearch();
}

/** Back to a walk-in sale, so the next ticket does not inherit this one. */
function detachCustomer() {
    customer.value = null;
    customerQuery.value = '';
    customerResults.value = [];
    customerOpen.value = false;
    closeSuggestions();
    form.value = {
        customer_name: 'Walk-in Customer',
        customer_phone: '',
        customer_address: 'Shop Counter',
    };
}

/* ------------------------------------------------------------- check out */

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
            customer_id: customer.value?.id ?? null,
            customer_name: form.value.customer_name,
            customer_phone: form.value.customer_phone,
            customer_address: form.value.customer_address,
            delivery_charge: Number(deliveryCharge.value || 0),
            // The raw figure and how to read it; the server does the sum again.
            discount_amount: Number(discountValue.value || 0),
            discount_type: discountType.value,
            payment_method: paymentMethod.value,
            source: source.value,
            notes: notes.value.trim() || null,
            paid_amount: paidAmount.value === null || paidAmount.value === '' ? null : Number(paidAmount.value),
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

/* ------------------------------------------------------------- shortcuts */

function onDocumentClick(event) {
    if (searchBox.value && !searchBox.value.contains(event.target)) {
        showResults.value = false;
    }

    if (categoryBox.value && !categoryBox.value.contains(event.target)) {
        categoryOpen.value = false;
    }

    if (customerBox.value && !customerBox.value.contains(event.target)) {
        customerOpen.value = false;
    }
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        if (suggestFor.value) {
            closeSuggestions();

            return;
        }

        if (showCheckout.value) {
            if (!submitting.value) showCheckout.value = false;
        } else if (categoryOpen.value) {
            categoryOpen.value = false;
        } else if (customerOpen.value) {
            customerOpen.value = false;
        } else {
            showResults.value = false;
        }
        return;
    }

    // F2 gets back to the search box from anywhere, F4 opens the payment
    // dialog — the two keys a counter reaches for without looking.
    if (event.key === 'F2') {
        event.preventDefault();
        focusSearch();
        return;
    }

    if (event.key === 'F4' && !showCheckout.value) {
        event.preventDefault();
        openCheckout();
    }
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onKeydown);
    focusSearch();

    // The pane changes height when the window does and width when the sidebar
    // collapses, and either changes how many tiles fit.
    nextTick(measurePage);

    if (window.ResizeObserver && boardBody.value) {
        resizeObserver = new ResizeObserver(() => measurePage());
        resizeObserver.observe(boardBody.value);
    } else {
        window.addEventListener('resize', measurePage);
    }
});

onBeforeUnmount(() => {
    clearTimeout(searchTimer);
    clearTimeout(customerTimer);
    clearTimeout(suggestTimer);
    clearTimeout(alertTimer);
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onKeydown);
    resizeObserver?.disconnect();
    window.removeEventListener('resize', measurePage);
});
</script>

<template>
    <div class="pos">
        <!-- ============================ the board ============================ -->
        <section class="pos-board">
            <header class="pos-board-head">
              <div class="pos-head-row">
                <!-- Category picker: a dropdown you can type into, so a shop
                     with thirty categories is still one keystroke away. -->
                <div v-if="categories.length" ref="categoryBox" class="pos-cat">
                    <button type="button" class="pos-cat-btn" :class="{ 'is-open': categoryOpen }" @click="toggleCategories">
                        <i class="bi bi-funnel"></i>
                        <span class="pos-cat-label">{{ activeCategoryLabel }}</span>
                        <span class="pos-cat-count">{{ visibleItems.length }}</span>
                        <i class="bi bi-chevron-down pos-cat-caret"></i>
                    </button>

                    <div v-if="categoryOpen" class="pos-cat-panel">
                        <div class="pos-cat-find">
                            <i class="bi bi-search"></i>
                            <input
                                ref="categoryInput"
                                v-model="categoryQuery"
                                type="text"
                                placeholder="Filter categories…"
                                autocomplete="off"
                                @keydown.enter.prevent="pickHighlightedCategory"
                                @keydown.down.prevent="moveCategoryHighlight(1)"
                                @keydown.up.prevent="moveCategoryHighlight(-1)"
                            >
                        </div>

                        <ul class="pos-cat-list">
                            <li v-if="!filteredCategories.length" class="pos-cat-none">No category matched that.</li>
                            <li v-for="(option, index) in filteredCategories" :key="option.id ?? 'all'">
                                <button
                                    type="button"
                                    :class="{
                                        'is-highlighted': index === categoryHighlight,
                                        'is-active': activeCategory === option.id,
                                    }"
                                    @mouseenter="categoryHighlight = index"
                                    @click="pickCategory(option)"
                                >
                                    <i class="bi" :class="activeCategory === option.id ? 'bi-check2' : 'bi-dot'"></i>
                                    <span>{{ option.name }}</span>
                                    <small>{{ option.count }}</small>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <div ref="searchBox" class="pos-search">
                    <i class="bi pos-search-icon" :class="searching ? 'bi-hourglass-split' : 'bi-search'"></i>
                    <input
                        ref="searchInput"
                        v-model="query"
                        type="text"
                        class="pos-search-input"
                        placeholder="Search a product, or scan a barcode…"
                        autocomplete="off"
                        @focus="showResults = results.length > 0"
                        @keydown.enter.prevent="pickHighlighted"
                        @keydown.down.prevent="moveHighlight(1)"
                        @keydown.up.prevent="moveHighlight(-1)"
                    >
                    <kbd class="pos-kbd">F2</kbd>

                    <div v-if="showResults" class="pos-results">
                        <p v-if="searching" class="pos-results-empty">Searching…</p>
                        <p v-else-if="!results.length" class="pos-results-empty">Nothing matched that.</p>
                        <button
                            v-for="(item, index) in results"
                            :key="item.id"
                            type="button"
                            class="pos-result"
                            :class="{ 'is-active': index === highlighted, 'is-out': item.stock <= 0 }"
                            @mouseenter="highlighted = index"
                            @click="pickResult(item)"
                        >
                            <img :src="item.image" :alt="item.product_name" class="pos-result-img">
                            <span class="pos-result-text">
                                <strong>{{ item.product_name }}</strong>
                                <small>{{ item.name }} · SKU {{ item.sku || '—' }}</small>
                            </span>
                            <span class="pos-result-meta">
                                <strong>{{ money(item.price) }}</strong>
                                <small :class="item.stock <= 0 ? 'is-out' : item.stock < 10 ? 'is-low' : ''">
                                    {{ item.stock <= 0 ? 'Out of stock' : item.stock + ' left' }}
                                </small>
                            </span>
                        </button>
                    </div>
                </div>
              </div>
            </header>

            <div ref="boardBody" class="pos-board-body">
                <transition name="pos-fade">
                    <div v-if="alert" class="pos-alert" :class="alertClass">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span>{{ alert.text }}</span>
                        <button type="button" class="pos-alert-close" @click="alert = null">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </transition>

                <div v-if="!visibleItems.length" class="pos-empty">
                    <i class="bi bi-box-seam"></i>
                    <p>{{ items.length ? 'Nothing in this category.' : 'No products available yet.' }}</p>
                </div>

                <div v-else ref="gridEl" class="pos-grid">
                    <button
                        v-for="item in pagedItems"
                        :key="item.id"
                        type="button"
                        class="pos-tile"
                        :disabled="stockLeft(item) <= 0"
                        @click="addToCart(item)"
                    >
                        <span class="pos-tile-media">
                            <img :src="item.image" :alt="item.product_name" loading="lazy">
                            <span
                                class="pos-tile-stock"
                                :class="stockLeft(item) <= 0 ? 'is-out' : stockLeft(item) < 5 ? 'is-low' : ''"
                            >{{ stockLeft(item) <= 0 ? 'Out' : stockLeft(item) }}</span>
                        </span>
                        <span class="pos-tile-body">
                            <strong class="pos-tile-name">{{ item.product_name }}</strong>
                            <small class="pos-tile-variant">{{ item.name }}</small>
                            <span class="pos-tile-price">
                                {{ money(item.price) }}
                                <s v-if="item.on_sale">{{ money(item.list_price) }}</s>
                            </span>
                        </span>
                    </button>
                </div>

                <div v-if="visibleItems.length" ref="moreEl" class="pos-more">
                    <p class="pos-more-count">
                        Showing {{ pagedItems.length }} of {{ visibleItems.length }}
                    </p>
                    <button v-if="remaining" type="button" class="pos-more-btn" @click="loadMore">
                        <i class="bi bi-arrow-down-circle"></i>
                        Load {{ nextBatch }} more
                        <span>{{ remaining }} left</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- ============================ the ticket =========================== -->
        <aside class="pos-ticket">
            <header class="pos-ticket-head">
                <div class="pos-ticket-title">
                    <h2><i class="bi bi-receipt"></i> Current Order</h2>
                    <span v-if="itemCount" class="pos-ticket-count">{{ itemCount }} item{{ itemCount === 1 ? '' : 's' }}</span>
                </div>

                <!-- Who is buying, chosen while the order is being rung up
                     rather than at payment time, when the queue is waiting. -->
                <div ref="customerBox" class="pos-who">
                    <div v-if="customer" class="pos-who-on">
                        <i class="bi bi-person-check-fill"></i>
                        <span class="pos-who-text">
                            <strong>{{ customer.name }}</strong>
                            <small>{{ customer.phone || 'no phone' }} · {{ customer.orders_count }} order(s)</small>
                        </span>
                        <button type="button" class="pos-who-drop" title="Back to a walk-in" @click="detachCustomer">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <button v-else type="button" class="pos-who-btn" @click="toggleCustomerFinder">
                        <i class="bi bi-person-plus"></i>
                        <span>Walk-in customer</span>
                        <small>Add</small>
                    </button>

                    <div v-if="customerOpen && !customer" class="pos-who-panel">
                        <div class="pos-who-find">
                            <i class="bi bi-search"></i>
                            <input
                                ref="customerInput"
                                v-model="customerQuery"
                                type="text"
                                placeholder="Find by phone or name…"
                                autocomplete="off"
                            >
                        </div>
                        <ul class="pos-who-list">
                            <li v-if="customerQuery.trim().length < 2" class="pos-who-note">
                                Type at least two characters.
                            </li>
                            <li v-else-if="customerSearching" class="pos-who-note">Searching…</li>
                            <li v-else-if="!customerResults.length" class="pos-who-note">
                                No match — the sale stays a walk-in.
                            </li>
                            <li v-for="found in customerResults" :key="found.id">
                                <button type="button" @click="pickCustomer(found)">
                                    <strong>{{ found.name }}</strong>
                                    <small>{{ found.phone || '—' }} · {{ found.orders_count }} order(s)</small>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <div class="pos-ticket-lines">
                <div v-if="!cart.length" class="pos-empty is-compact">
                    <i class="bi bi-cart3"></i>
                    <p>Scan or tap a product to start.</p>
                </div>

                <div v-for="line in cart" v-else :key="line.id" class="pos-line">
                    <img :src="line.image" :alt="line.product_name" class="pos-line-img">
                    <div class="pos-line-main">
                        <div class="pos-line-top">
                            <span class="pos-line-name">
                                <strong>{{ line.product_name }}</strong>
                                <small>{{ line.name }} · {{ money(line.price) }} each</small>
                                <!-- The line rings up at the shelf price, so say
                                     where the reduction underneath came from. -->
                                <span v-if="line.offerPrice < line.price" class="pos-line-offer">
                                    <i class="bi bi-tag-fill"></i>
                                    Offer −{{ money((line.price - line.offerPrice) * line.quantity) }}
                                </span>
                            </span>
                            <button type="button" class="pos-line-drop" title="Remove" @click="removeLine(line)">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <div class="pos-line-bottom">
                            <div class="pos-stepper">
                                <button type="button" @click="changeQty(line, -1)"><i class="bi bi-dash"></i></button>
                                <input
                                    type="number"
                                    min="1"
                                    :max="line.stock"
                                    :value="line.quantity"
                                    @change="setQty(line, $event.target.value)"
                                >
                                <button type="button" :disabled="line.quantity >= line.stock" @click="changeQty(line, 1)">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                            <span class="pos-line-total">{{ money(line.price * line.quantity) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="pos-ticket-foot">
                <div class="pos-sum">
                    <span>Subtotal</span>
                    <span>{{ money(subtotal) }}</span>
                </div>

                <div class="pos-adjust">
                    <label>
                        <span>
                            Discount
                            <button
                                type="button"
                                class="pos-unit"
                                :title="discountType === 'flat' ? 'Switch to a percentage' : 'Switch to taka'"
                                @click="switchDiscountType"
                            >{{ discountType === 'flat' ? '৳' : '%' }}</button>
                            <button
                                v-if="discountTouched && offerSaving > 0"
                                type="button"
                                class="pos-unit pos-unit-reset"
                                title="Back to the products' own offers"
                                @click="resetDiscount"
                            ><i class="bi bi-arrow-counterclockwise"></i></button>
                        </span>
                        <input
                            v-model.number="discountValue"
                            type="number"
                            min="0"
                            step="1"
                            @input="discountTouched = true"
                        >
                    </label>
                    <label>
                        <span>Delivery ৳</span>
                        <input v-model.number="deliveryCharge" type="number" min="0" step="1">
                    </label>
                </div>

                <div v-if="discountAmount > 0" class="pos-sum pos-sum-off">
                    <span>Discount<template v-if="discountType === 'percent'"> ({{ discountValue }}%)</template></span>
                    <span>− {{ money(discountAmount) }}</span>
                </div>

                <div class="pos-total">
                    <span>Total</span>
                    <strong>{{ money(grandTotal) }}</strong>
                </div>

                <button type="button" class="pos-charge" :disabled="!cart.length" @click="openCheckout">
                    <span>Charge {{ money(grandTotal) }}</span>
                    <kbd>F4</kbd>
                </button>
                <button type="button" class="pos-clear" :disabled="!cart.length" @click="clearCart">
                    Clear order
                </button>
            </footer>
        </aside>

        <!-- Payment dialog, hand rolled so it does not depend on Bootstrap's JS -->
        <Teleport to="body">
            <div v-if="showCheckout" class="pos-modal" @click.self="submitting || (showCheckout = false)">
                <div class="pos-modal-card" role="dialog" aria-modal="true" aria-label="Take payment">
                    <header class="pos-modal-head">
                        <h3>Take payment</h3>
                        <button type="button" :disabled="submitting" @click="showCheckout = false">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </header>

                    <form class="pos-modal-body" @submit.prevent="submitOrder">
                        <div class="pos-modal-due">
                            <span>Amount due</span>
                            <strong>{{ money(grandTotal) }}</strong>
                        </div>

                        <!-- The customer is chosen up on the order panel; these
                             fields are what actually goes on the order, filled
                             in from that choice or typed for a walk-in. -->
                        <div class="pos-field">
                            <label for="pos-cust-name"><span>Customer name</span></label>
                            <input
                                id="pos-cust-name"
                                v-model="form.customer_name"
                                type="text"
                                required
                                autocomplete="off"
                                @input="onFormInput('name')"
                                @focus="onFormFocus('name')"
                            >
                            <!-- mousedown, not click: the input blurs first and
                                 a click handler would never fire. -->
                            <ul v-if="suggestFor === 'name'" class="pos-suggest">
                                <li v-if="suggestSearching" class="pos-suggest-note">Searching…</li>
                                <li v-else-if="!suggestResults.length" class="pos-suggest-note">
                                    No customer by that name — this stays a new one.
                                </li>
                                <li v-for="found in suggestResults" :key="found.id">
                                    <button type="button" @mousedown.prevent="pickCustomer(found)">
                                        <strong>{{ found.name }}</strong>
                                        <small>{{ found.phone || '—' }} · {{ found.orders_count }} order(s)</small>
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <div class="pos-field">
                            <label for="pos-cust-phone"><span>Phone number</span></label>
                            <input
                                id="pos-cust-phone"
                                v-model="form.customer_phone"
                                type="text"
                                required
                                placeholder="01XXXXXXXXX"
                                autocomplete="off"
                                @input="onFormInput('phone')"
                                @focus="onFormFocus('phone')"
                            >
                            <ul v-if="suggestFor === 'phone'" class="pos-suggest">
                                <li v-if="suggestSearching" class="pos-suggest-note">Searching…</li>
                                <li v-else-if="!suggestResults.length" class="pos-suggest-note">
                                    No customer on that number — this stays a new one.
                                </li>
                                <li v-for="found in suggestResults" :key="found.id">
                                    <button type="button" @mousedown.prevent="pickCustomer(found)">
                                        <strong>{{ found.phone || '—' }}</strong>
                                        <small>{{ found.name }} · {{ found.orders_count }} order(s)</small>
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <label class="pos-field">
                            <span>Address</span>
                            <textarea v-model="form.customer_address" rows="2" required></textarea>
                        </label>

                        <div v-if="paymentMethods.length" class="pos-field">
                            <span>Paid by</span>
                            <div class="pos-pay-methods">
                                <button
                                    v-for="method in paymentMethods"
                                    :key="method.value"
                                    type="button"
                                    class="pos-pay-method"
                                    :class="{ 'is-active': paymentMethod === method.value }"
                                    @click="paymentMethod = method.value"
                                >
                                    {{ method.label }}
                                </button>
                            </div>
                        </div>

                        <div v-if="sources.length > 1" class="pos-field">
                            <span>Sale came in by</span>
                            <div class="pos-pay-methods">
                                <button
                                    v-for="option in sources"
                                    :key="option.value"
                                    type="button"
                                    class="pos-pay-method"
                                    :class="{ 'is-active': source === option.value }"
                                    @click="source = option.value"
                                >
                                    {{ option.label }}
                                </button>
                            </div>
                        </div>

                        <label class="pos-field">
                            <span>Note <small>(optional)</small></span>
                            <textarea v-model="notes" rows="2" placeholder="Anything to remember about this sale…"></textarea>
                        </label>

                        <label class="pos-field">
                            <span>Cash received <small>(optional)</small></span>
                            <input v-model="paidAmount" type="number" min="0" step="1" placeholder="0">
                        </label>

                        <div v-if="changeDue !== null" class="pos-change" :class="{ 'is-short': changeDue < 0 }">
                            <span>{{ changeDue < 0 ? 'Still owing' : 'Change to give' }}</span>
                            <strong>{{ money(changeDue < 0 ? -changeDue : changeDue) }}</strong>
                        </div>

                        <button type="submit" class="pos-confirm" :disabled="submitting">
                            <span v-if="submitting" class="spinner-border spinner-border-sm"></span>
                            <i v-else class="bi bi-printer"></i>
                            {{ submitting ? 'Processing…' : 'Confirm & print' }}
                        </button>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
/*
   A counter workspace: two panes side by side, each scrolling on its own, the
   page itself fixed. Colours come from the brand tokens in css/brand.css.
*/
.pos {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 400px;
    gap: 0;
    height: 100%;
    min-height: 0;
    background: #eef1ea;
}

/* ------------------------------------------------------------- the board */

.pos-board { display: flex; flex-direction: column; min-height: 0; min-width: 0; }

.pos-board-head {
    flex: 0 0 auto;
    background: #fff;
    border-bottom: 1px solid var(--gray-200);
    padding: 14px 20px;
}
.pos-head-row { display: flex; align-items: stretch; gap: 10px; }

.pos-search { position: relative; display: flex; align-items: center; flex: 1 1 auto; min-width: 0; }
.pos-search-icon {
    position: absolute;
    left: 18px;
    font-size: 1.05rem;
    color: var(--primary);
    pointer-events: none;
}
.pos-search-input {
    width: 100%;
    border: 2px solid var(--gray-200);
    border-radius: 999px;
    background: var(--gray-100);
    padding: 12px 62px 12px 46px;
    font-family: inherit;
    font-size: 0.95rem;
    color: var(--dark);
    outline: none;
    transition: var(--transition);
}
.pos-search-input:focus {
    background: #fff;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.12);
}
.pos-kbd {
    position: absolute;
    right: 14px;
    background: #fff;
    border: 1px solid var(--gray-200);
    border-bottom-width: 2px;
    border-radius: 5px;
    padding: 2px 7px;
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--gray-500);
    pointer-events: none;
}

.pos-results {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    right: 0;
    z-index: 1050;
    background: #fff;
    border-radius: var(--radius);
    box-shadow: 0 14px 40px rgba(30, 74, 1, 0.18);
    max-height: 60vh;
    overflow-y: auto;
    padding: 6px;
}
.pos-results-empty { margin: 0; padding: 18px; text-align: center; color: var(--gray-500); font-size: 0.85rem; }
.pos-result {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    border: none;
    background: none;
    border-radius: var(--radius-sm);
    padding: 9px 10px;
    text-align: left;
    cursor: pointer;
}
.pos-result.is-active { background: var(--leaf-tint); }
.pos-result.is-out { opacity: 0.5; }
.pos-result-img { width: 38px; height: 38px; border-radius: 8px; object-fit: contain; background: var(--gray-100); flex: 0 0 38px; }
.pos-result-text { display: flex; flex-direction: column; min-width: 0; }
.pos-result-text strong { font-size: 0.85rem; font-weight: 600; color: var(--dark); }
.pos-result-text small { font-size: 0.72rem; color: var(--gray-500); }
.pos-result-meta { margin-left: auto; display: flex; flex-direction: column; align-items: flex-end; }
.pos-result-meta strong { font-size: 0.85rem; color: var(--primary-dark); }
.pos-result-meta small { font-size: 0.68rem; color: var(--gray-500); }
.pos-result-meta small.is-low { color: var(--accent-text); font-weight: 600; }
.pos-result-meta small.is-out { color: var(--danger); font-weight: 600; }

/* Category picker. A row of chips ran off the edge once a shop had more than a
   handful; a dropdown you can type into stays one line whatever the count. */
.pos-cat { position: relative; flex: 0 0 auto; }
.pos-cat-btn {
    display: flex;
    align-items: center;
    gap: 9px;
    height: 100%;
    min-width: 210px;
    border: 2px solid var(--gray-200);
    background: var(--gray-100);
    border-radius: 999px;
    padding: 11px 16px;
    font-family: inherit;
    font-size: 0.86rem;
    font-weight: 600;
    color: var(--dark);
    cursor: pointer;
    transition: var(--transition);
}
.pos-cat-btn > .bi-funnel { color: var(--primary); }
.pos-cat-btn:hover,
.pos-cat-btn.is-open { background: #fff; border-color: var(--primary); }
.pos-cat-label {
    flex: 1 1 auto;
    text-align: left;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.pos-cat-count {
    background: var(--leaf-tint);
    color: var(--primary-dark);
    border-radius: 999px;
    padding: 1px 8px;
    font-size: 0.7rem;
    font-weight: 700;
}
.pos-cat-caret { font-size: 0.7rem; color: var(--gray-500); transition: transform 0.2s ease; }
.pos-cat-btn.is-open .pos-cat-caret { transform: rotate(180deg); }

.pos-cat-panel {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    z-index: 1060;
    width: 280px;
    background: #fff;
    border-radius: var(--radius);
    box-shadow: 0 14px 40px rgba(30, 74, 1, 0.2);
    overflow: hidden;
}
.pos-cat-find { display: flex; align-items: center; gap: 8px; padding: 10px 12px; border-bottom: 1px solid var(--gray-200); }
.pos-cat-find i { color: var(--gray-500); font-size: 0.85rem; }
.pos-cat-find input {
    flex: 1 1 auto;
    border: none;
    outline: none;
    font-family: inherit;
    font-size: 0.85rem;
    color: var(--dark);
    background: none;
}
.pos-cat-list { list-style: none; margin: 0; padding: 5px; max-height: 300px; overflow-y: auto; }
.pos-cat-none { padding: 14px 12px; font-size: 0.8rem; color: var(--gray-500); text-align: center; }
.pos-cat-list button {
    display: flex;
    align-items: center;
    gap: 9px;
    width: 100%;
    border: none;
    background: none;
    border-radius: 7px;
    padding: 8px 10px;
    font-family: inherit;
    font-size: 0.85rem;
    color: var(--dark);
    text-align: left;
    cursor: pointer;
}
.pos-cat-list button > .bi { font-size: 0.8rem; color: var(--gray-500); width: 14px; }
.pos-cat-list button > span { flex: 1 1 auto; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pos-cat-list button > small { font-size: 0.72rem; color: var(--gray-500); }
.pos-cat-list button.is-highlighted { background: var(--gray-100); }
.pos-cat-list button.is-active { background: var(--leaf-tint); font-weight: 600; color: var(--primary-dark); }
.pos-cat-list button.is-active > .bi,
.pos-cat-list button.is-active > small { color: var(--primary-dark); }

/*
   The page fills to the last row that fits, so this scrolls only once someone
   has pressed Load more. The gutter is reserved either way — without it the
   pane narrows the moment a scrollbar appears, which changes the column count,
   which changes how many tiles fit, and the measurement never settles.
*/
.pos-board-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    scrollbar-gutter: stable;
    padding: 16px 20px 24px;
}

.pos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 12px;
}
.pos-tile {
    display: flex;
    flex-direction: column;
    border: 1px solid var(--gray-200);
    background: #fff;
    border-radius: var(--radius);
    padding: 0;
    overflow: hidden;
    text-align: left;
    cursor: pointer;
    transition: var(--transition);
}
.pos-tile:hover:not(:disabled) {
    transform: translateY(-3px);
    border-color: var(--primary);
    box-shadow: 0 10px 22px rgba(30, 74, 1, 0.12);
}
.pos-tile:active:not(:disabled) { transform: translateY(0); }
.pos-tile:disabled { opacity: 0.45; cursor: not-allowed; }

.pos-tile-media { position: relative; display: block; background: var(--gray-100); }
.pos-tile-media img { width: 100%; height: 104px; object-fit: contain; padding: 8px; display: block; }
.pos-tile-stock {
    position: absolute;
    top: 6px;
    right: 6px;
    background: var(--primary);
    color: #fff;
    font-size: 0.66rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 999px;
}
.pos-tile-stock.is-low { background: var(--accent-dark); }
.pos-tile-stock.is-out { background: var(--gray-500); }

.pos-tile-body { display: flex; flex-direction: column; gap: 2px; padding: 9px 11px 11px; }
.pos-tile-name {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--dark);
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.pos-tile-variant { font-size: 0.7rem; color: var(--gray-500); }
.pos-tile-price { margin-top: 4px; font-size: 0.95rem; font-weight: 700; color: var(--primary-dark); }
.pos-tile-price s { margin-left: 5px; font-size: 0.72rem; font-weight: 400; color: var(--gray-500); }

/* The count and the Load more button that close the grid. */
.pos-more {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 20px 0 4px;
}
.pos-more-count { margin: 0; font-size: 0.78rem; color: var(--gray-500); }
.pos-more-btn {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    border: 1px solid var(--primary);
    background: #fff;
    color: var(--primary-dark);
    border-radius: 999px;
    padding: 9px 22px;
    font-size: 0.86rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
}
.pos-more-btn:hover { background: var(--primary); color: #fff; }
.pos-more-btn span {
    background: var(--gray-100);
    border-radius: 999px;
    padding: 1px 8px;
    font-size: 0.7rem;
    color: var(--gray-500);
}
.pos-more-btn:hover span { background: rgba(255, 255, 255, 0.22); color: #fff; }

/* ------------------------------------------------------------ the ticket */

.pos-ticket {
    display: flex;
    flex-direction: column;
    min-height: 0;
    background: #fff;
    border-left: 1px solid var(--gray-200);
}
.pos-ticket-head {
    flex: 0 0 auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid var(--gray-200);
}
.pos-ticket-title { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.pos-ticket-head h2 {
    display: flex;
    align-items: center;
    gap: 9px;
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: var(--primary-darker);
}
.pos-ticket-head h2 i { color: var(--accent-dark); }

/* Who is buying. Sits with the order rather than in the payment dialog, so it
   can be settled while the goods are still going through. */
.pos-who { position: relative; }
.pos-who-btn {
    display: flex;
    align-items: center;
    gap: 9px;
    width: 100%;
    border: 1px dashed var(--gray-200);
    background: var(--gray-100);
    border-radius: 10px;
    padding: 9px 12px;
    font-family: inherit;
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--gray-500);
    cursor: pointer;
    transition: var(--transition);
}
.pos-who-btn:hover { border-color: var(--primary); color: var(--primary-dark); background: #fff; }
.pos-who-btn > i { font-size: 1.05rem; }
.pos-who-btn > span { flex: 1 1 auto; text-align: left; }
.pos-who-btn > small {
    background: var(--primary);
    color: #fff;
    font-size: 0.68rem;
    font-weight: 700;
    padding: 2px 9px;
    border-radius: 999px;
}

.pos-who-on {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--leaf-tint);
    border: 1px solid rgba(var(--primary-rgb), 0.3);
    border-radius: 10px;
    padding: 8px 12px;
}
.pos-who-on > i { color: var(--primary); font-size: 1.05rem; }
.pos-who-text { display: flex; flex-direction: column; min-width: 0; }
.pos-who-text strong {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--primary-darker);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.pos-who-text small { font-size: 0.7rem; color: var(--primary-dark); opacity: 0.75; }
.pos-who-drop {
    margin-left: auto;
    border: none;
    background: none;
    color: var(--primary-dark);
    font-size: 0.7rem;
    opacity: 0.6;
    cursor: pointer;
    padding: 3px 5px;
    border-radius: 5px;
}
.pos-who-drop:hover { opacity: 1; background: rgba(255, 255, 255, 0.65); }

.pos-who-panel {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    right: 0;
    z-index: 1060;
    background: #fff;
    border-radius: var(--radius);
    box-shadow: 0 14px 40px rgba(30, 74, 1, 0.2);
    overflow: hidden;
}
.pos-who-find { display: flex; align-items: center; gap: 8px; padding: 10px 12px; border-bottom: 1px solid var(--gray-200); }
.pos-who-find i { color: var(--gray-500); font-size: 0.85rem; }
.pos-who-find input {
    flex: 1 1 auto;
    border: none;
    outline: none;
    background: none;
    font-family: inherit;
    font-size: 0.85rem;
    color: var(--dark);
}
.pos-who-list { list-style: none; margin: 0; padding: 5px; max-height: 260px; overflow-y: auto; }
.pos-who-note { padding: 12px; font-size: 0.78rem; color: var(--gray-500); text-align: center; }
.pos-who-list button {
    display: flex;
    flex-direction: column;
    width: 100%;
    border: none;
    background: none;
    border-radius: 7px;
    padding: 8px 10px;
    text-align: left;
    cursor: pointer;
}
.pos-who-list button:hover { background: var(--leaf-tint); }
.pos-who-list strong { font-size: 0.85rem; font-weight: 600; color: var(--dark); }
.pos-who-list small { font-size: 0.72rem; color: var(--gray-500); }
.pos-ticket-count {
    background: var(--leaf-tint);
    color: var(--primary-dark);
    font-size: 0.72rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 999px;
}

.pos-ticket-lines { flex: 1 1 auto; min-height: 0; overflow-y: auto; padding: 10px 14px; }

.pos-line { display: flex; gap: 11px; padding: 10px 0; border-bottom: 1px solid var(--gray-100); }
.pos-line:last-child { border-bottom: none; }
.pos-line-img { width: 44px; height: 44px; flex: 0 0 44px; border-radius: 8px; object-fit: contain; background: var(--gray-100); }
.pos-line-main { flex: 1 1 auto; min-width: 0; }
.pos-line-top { display: flex; align-items: flex-start; gap: 8px; }
.pos-line-name { display: flex; flex-direction: column; min-width: 0; }
.pos-line-name strong {
    font-size: 0.83rem;
    font-weight: 600;
    color: var(--dark);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.pos-line-name small { font-size: 0.7rem; color: var(--gray-500); }
.pos-line-offer {
    align-self: flex-start;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 3px;
    background: var(--gold-tint);
    color: var(--accent-text);
    font-size: 0.66rem;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 4px;
}

.pos-unit-reset { width: auto; padding: 0 5px; }
.pos-line-drop {
    margin-left: auto;
    border: none;
    background: none;
    color: var(--gray-500);
    font-size: 0.75rem;
    padding: 2px 4px;
    cursor: pointer;
    border-radius: 5px;
}
.pos-line-drop:hover { background: #fdeaea; color: var(--danger); }
.pos-line-bottom { display: flex; align-items: center; justify-content: space-between; margin-top: 7px; }
.pos-line-total { font-size: 0.9rem; font-weight: 700; color: var(--primary-dark); }

.pos-stepper { display: inline-flex; align-items: center; border: 1px solid var(--gray-200); border-radius: 8px; overflow: hidden; }
.pos-stepper button {
    border: none;
    background: var(--gray-100);
    color: var(--primary-dark);
    width: 28px;
    height: 28px;
    display: grid;
    place-items: center;
    font-size: 0.75rem;
    cursor: pointer;
    transition: var(--transition);
}
.pos-stepper button:hover:not(:disabled) { background: var(--primary); color: #fff; }
.pos-stepper button:disabled { opacity: 0.4; cursor: not-allowed; }
.pos-stepper input {
    width: 42px;
    height: 28px;
    border: none;
    text-align: center;
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--dark);
    outline: none;
    -moz-appearance: textfield;
    appearance: textfield;
}
.pos-stepper input::-webkit-outer-spin-button,
.pos-stepper input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

.pos-ticket-foot { flex: 0 0 auto; border-top: 1px solid var(--gray-200); background: var(--gray-100); padding: 14px 16px 16px; }
.pos-sum { display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--gray-500); margin-bottom: 10px; }
.pos-sum-off { color: var(--accent-text); font-weight: 600; }

/* The ৳ / % switch sits inside the discount field's own label. */
.pos-unit {
    border: 1px solid var(--gray-200);
    background: #fff;
    color: var(--primary-dark);
    border-radius: 5px;
    width: 20px;
    height: 18px;
    font-size: 0.7rem;
    font-weight: 700;
    line-height: 1;
    cursor: pointer;
    padding: 0;
    transition: var(--transition);
}
.pos-unit:hover { background: var(--primary); border-color: var(--primary); color: #fff; }

.pos-adjust { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px; }
.pos-adjust label { display: flex; flex-direction: column; gap: 4px; }
.pos-adjust span { font-size: 0.7rem; font-weight: 600; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.4px; }
.pos-adjust input {
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    background: #fff;
    padding: 7px 10px;
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--dark);
    outline: none;
    width: 100%;
}
.pos-adjust input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.12); }

.pos-total {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    padding: 12px 0;
    border-top: 2px dashed var(--gray-200);
    margin-bottom: 12px;
}
.pos-total span { font-size: 0.9rem; font-weight: 600; color: var(--dark); }
.pos-total strong { font-size: 1.5rem; font-weight: 700; color: var(--primary-dark); }

.pos-charge {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    border: none;
    border-radius: var(--radius);
    background: linear-gradient(120deg, var(--primary-dark), var(--primary));
    color: #fff;
    font-size: 1rem;
    font-weight: 700;
    padding: 14px;
    cursor: pointer;
    transition: var(--transition);
}
.pos-charge:hover:not(:disabled) { box-shadow: 0 8px 22px rgba(var(--primary-rgb), 0.32); transform: translateY(-1px); }
.pos-charge:disabled { background: var(--gray-200); color: var(--gray-500); cursor: not-allowed; }
.pos-charge kbd {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 5px;
    padding: 2px 7px;
    font-size: 0.68rem;
    font-weight: 700;
}
.pos-charge:disabled kbd { background: rgba(0, 0, 0, 0.06); }

.pos-clear {
    width: 100%;
    border: none;
    background: none;
    color: var(--gray-500);
    font-size: 0.8rem;
    font-weight: 600;
    padding: 9px;
    margin-top: 6px;
    border-radius: 8px;
    cursor: pointer;
}
.pos-clear:hover:not(:disabled) { background: #fdeaea; color: var(--danger); }
.pos-clear:disabled { opacity: 0.45; cursor: not-allowed; }

/* -------------------------------------------------------- alerts & empty */

.pos-alert {
    display: flex;
    align-items: center;
    gap: 10px;
    border-radius: var(--radius);
    padding: 11px 14px;
    margin-bottom: 14px;
    font-size: 0.86rem;
    font-weight: 500;
}
.pos-alert-danger { background: #fdeaea; color: #a11b1b; }
.pos-alert-success { background: var(--leaf-tint); color: var(--primary-dark); }
.pos-alert span { flex: 1 1 auto; }
.pos-alert-close { border: none; background: none; color: inherit; opacity: 0.6; cursor: pointer; font-size: 0.75rem; }
.pos-alert-close:hover { opacity: 1; }

.pos-fade-enter-active, .pos-fade-leave-active { transition: opacity 0.2s ease; }
.pos-fade-enter-from, .pos-fade-leave-to { opacity: 0; }

.pos-empty { text-align: center; color: var(--gray-500); padding: 56px 20px; }
.pos-empty i { font-size: 2.4rem; opacity: 0.25; display: block; margin-bottom: 10px; }
.pos-empty p { margin: 0; font-size: 0.88rem; }
.pos-empty.is-compact { padding: 40px 16px; }

/* ------------------------------------------------------------- the modal */

.pos-modal {
    position: fixed;
    inset: 0;
    z-index: 1080;
    background: rgba(20, 40, 5, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    overflow-y: auto;
}
.pos-modal-card {
    background: #fff;
    border-radius: var(--radius-lg);
    width: 100%;
    max-width: 440px;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
    overflow: hidden;
}
.pos-modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(120deg, var(--primary-dark), var(--primary));
    color: #fff;
    padding: 15px 20px;
}
.pos-modal-head h3 { margin: 0; font-size: 1.02rem; font-weight: 700; color: #fff; }
.pos-modal-head button { border: none; background: rgba(255, 255, 255, 0.15); color: #fff; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; }
.pos-modal-head button:hover:not(:disabled) { background: rgba(255, 255, 255, 0.3); }

.pos-modal-body { padding: 20px; display: flex; flex-direction: column; gap: 13px; }
.pos-modal-due {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    background: var(--leaf-tint);
    border-radius: var(--radius);
    padding: 13px 16px;
}
.pos-modal-due span { font-size: 0.85rem; font-weight: 600; color: var(--primary-dark); }
.pos-modal-due strong { font-size: 1.5rem; font-weight: 700; color: var(--primary-dark); }

.pos-field { display: flex; flex-direction: column; gap: 5px; position: relative; }

/* Typeahead under the name and phone boxes, so a regular can be attached from
   the payment form without going back up to the picker. */
.pos-suggest {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 5;
    list-style: none;
    margin: 4px 0 0;
    padding: 4px;
    background: #fff;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    box-shadow: 0 10px 26px rgba(30, 74, 1, 0.16);
    max-height: 210px;
    overflow-y: auto;
}
.pos-suggest-note { padding: 10px 12px; font-size: 0.78rem; color: var(--gray-500); }
.pos-suggest button {
    display: flex;
    flex-direction: column;
    width: 100%;
    border: none;
    background: none;
    border-radius: 6px;
    padding: 7px 10px;
    text-align: left;
    cursor: pointer;
}
.pos-suggest button:hover { background: var(--leaf-tint); }
.pos-suggest strong { font-size: 0.85rem; font-weight: 600; color: var(--dark); }
.pos-suggest small { font-size: 0.72rem; color: var(--gray-500); }
.pos-field > span,
.pos-field > label > span {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
.pos-field > label { margin: 0; }
.pos-field input,
.pos-field textarea {
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    padding: 9px 12px;
    font-family: inherit;
    font-size: 0.9rem;
    color: var(--dark);
    outline: none;
    resize: vertical;
}
.pos-field input:focus,
.pos-field textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.12); }


/* Change due, worked out live as the cashier types what was handed over. */
.pos-change {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    border-radius: var(--radius);
    padding: 12px 16px;
    background: var(--gold-tint);
}
.pos-change span { font-size: 0.85rem; font-weight: 600; color: var(--accent-text); }
.pos-change strong { font-size: 1.35rem; font-weight: 700; color: var(--accent-text); }
.pos-change.is-short { background: #fdeaea; }
.pos-change.is-short span,
.pos-change.is-short strong { color: #a11b1b; }

.pos-pay-methods { display: flex; flex-wrap: wrap; gap: 8px; }
.pos-pay-method {
    border: 1px solid var(--gray-200);
    background: #fff;
    border-radius: 999px;
    padding: 7px 15px;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--dark);
    cursor: pointer;
    transition: var(--transition);
}
.pos-pay-method:hover { border-color: var(--primary-light); }
.pos-pay-method.is-active { background: var(--primary); border-color: var(--primary); color: #fff; }

.pos-confirm {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    border: none;
    border-radius: var(--radius);
    background: var(--accent);
    color: var(--primary-darker);
    font-size: 0.98rem;
    font-weight: 700;
    padding: 14px;
    margin-top: 4px;
    cursor: pointer;
    transition: var(--transition);
}
.pos-confirm:hover:not(:disabled) { background: var(--accent-dark); color: #fff; }
.pos-confirm:disabled { opacity: 0.65; cursor: not-allowed; }

/* ------------------------------------------------------------ responsive */

@media (max-width: 1199.98px) {
    .pos { grid-template-columns: minmax(0, 1fr) 340px; }
}

@media (max-width: 991.98px) {
    /* The page scrolls again below this width, so the ticket sits underneath
       the board rather than fighting it for a half-height column. */
    .pos { display: block; height: auto; background: transparent; }
    .pos-board-body { overflow: visible; }
    .pos-ticket { border-left: none; border-top: 1px solid var(--gray-200); }
    .pos-ticket-lines { overflow: visible; }
    .pos-grid { grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); }
    .pos-kbd, .pos-charge kbd { display: none; }
    .pos-head-row { flex-direction: column; }
    .pos-cat-btn { width: 100%; }
    .pos-cat-panel { width: 100%; }
}
</style>
